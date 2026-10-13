<?php

namespace App\Services;

use App\Models\CompanyDocument;
use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CompanyScopeService
{
    // Explicit vocabulary avoids treating generic procurement words as capability.
    private const CONCEPTS = [
        'arquitetura' => 'arquitet\w*|7111\D*1\D*00',
        'engenharia' => 'engenharia|7112\D*0\D*00',
        'estrutural' => 'estrutur\w*',
        'elétrica' => 'eletric\w*',
        'hidrossanitária' => 'hidrossanit\w*|hidraulic\w*|sanitari\w*',
        'urbanismo' => 'urbanis\w*',
        'paisagismo' => 'paisag\w*',
        'topografia' => 'topograf\w*',
        'geotecnia' => 'geotec\w*|sondag\w*',
        'pavimentação' => 'paviment\w*',
        'drenagem' => 'drenag\w*',
        'incêndio' => 'incend\w*',
        'climatização' => 'climatiz\w*|ar condicionado',
        'saneamento' => 'saneamento',
    ];

    private ?Collection $documents = null;

    public function refresh(): self
    {
        $this->documents = CompanyDocument::where('status', 'ready')
            ->whereIn('kind', ['certificate', 'attestation', 'rrt', 'cnpj'])->get();

        return $this;
    }

    private function concepts(string $text): array
    {
        $text = Str::lower(Str::ascii($text));

        return array_keys(array_filter(self::CONCEPTS, fn ($pattern) => preg_match('/\b(?:'.$pattern.')\b/u', $text)));
    }

    public function hasUsableDocuments(): bool
    {
        $this->refresh();

        return $this->documents->contains(fn ($doc) => (! $doc->expires_at || $doc->expires_at->copy()->endOfDay()->gte(now()))
            && collect($doc->pages ?? [])->contains(fn ($page) => mb_strlen(trim($page['text'] ?? '')) >= 25));
    }

    public function assess(string $object, $deadline = null): array
    {
        if ($this->documents === null) {
            $this->refresh();
        }
        $date = $deadline ? Carbon::parse($deadline)->max(now()) : now();
        $documents = $this->documents->filter(fn ($doc) => ! $doc->expires_at || $doc->expires_at->copy()->endOfDay()->gte($date));
        if ($documents->isEmpty()) {
            return ['status' => 'pending', 'reason' => 'Cadastre documentos da empresa com texto extraído e validade suficiente para o prazo do edital.', 'evidence' => []];
        }
        [$scope] = app(ScopeClassifier::class)->classify($object);
        if ($scope !== 'relevant') {
            return ['status' => 'incompatible', 'reason' => 'Objeto fora do escopo de projetos ou insuficiente para confirmar esse escopo.', 'evidence' => []];
        }
        $required = $this->concepts($object);
        if (! $required) {
            return ['status' => 'pending', 'reason' => 'O objeto não identifica especialidades reconhecidas para o confronto documental.', 'evidence' => []];
        }
        $evidence = [];
        $covered = [];
        foreach ($documents as $doc) {
            foreach ($doc->pages ?? [] as $page) {
                $text = $page['text'] ?? '';
                if (mb_strlen(trim($text)) < 25) {
                    continue;
                }
                $hits = array_values(array_intersect($required, $this->concepts($text)));
                if (! $hits) {
                    continue;
                }
                $covered = array_merge($covered, $hits);
                $normalized = Str::lower(Str::ascii($text));
                preg_match('/\b(?:'.self::CONCEPTS[$hits[0]].')\b/u', $normalized, $match, PREG_OFFSET_CAPTURE);
                $offset = isset($match[0]) ? mb_strlen(substr($normalized, 0, $match[0][1])) : 0;
                $evidence[] = ['document_id' => $doc->id, 'name' => $doc->name, 'kind' => $doc->kind, 'page' => $page['number'],
                    'concepts' => $hits, 'excerpt' => mb_substr($text, max(0, $offset - 150), 600)];
            }
        }
        $missing = array_values(array_diff($required, $covered));

        return ['status' => $missing ? 'incompatible' : 'compatible',
            'reason' => $missing ? 'Sem evidência documental para: '.implode(', ', $missing).'.' : 'Especialidades do objeto encontradas no acervo válido. Confronto textual preliminar; revise os requisitos do edital.',
            'evidence' => $evidence, 'missing' => $missing];
    }

    public function compatibleIds(): array
    {
        $this->refresh();
        $ids = [];
        Opportunity::whereDoesntHave('dismissal')->where('deadline', '>=', Opportunity::openDeadlineBoundary())->select('id', 'object', 'deadline')->chunkById(250, function ($items) use (&$ids) {
            foreach ($items as $item) {
                if ($this->assess($item->object, $item->deadline)['status'] === 'compatible') {
                    $ids[] = $item->id;
                }
            }
        });

        return $ids;
    }

    public function notice(): string
    {
        return 'Busca baseada em certidões, atestados, RRT e habilitação do CNPJ com texto extraído. A correspondência de especialidades é preliminar e não comprova habilitação integral. Documentos sem texto suficiente precisam de revisão/OCR.';
    }
}
