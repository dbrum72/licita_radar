<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PncpClient
{
    public function request(string $url, array $query = [], array $options = []): \Illuminate\Http\Client\Response
    {
        // Pace requests and honor rate limits. Only trusted, fixed PNCP URLs are used.
        for ($attempt = 0; $attempt < 4; $attempt++) {
            if (!app()->environment('testing')) usleep((int)config('radar.request_delay_ms') * 1000);
            $response = Http::acceptJson()->withOptions(['allow_redirects'=>false, ...$options])->connectTimeout(10)->timeout(60)->get($url, $query);
            if ($response->status() !== 429 && !$response->serverError()) return $response->throw();
            if ($attempt === 3) return $response->throw();
            $retryAfter = $response->header('Retry-After');
            $wait = is_numeric($retryAfter) ? (int)$retryAfter : max(0,(strtotime($retryAfter ?: '') ?: time())-time());
            if (!app()->environment('testing')) sleep(min(60,max($wait,15 * (2 ** $attempt))));
        }
        throw new RuntimeException('Falha ao consultar PNCP.');
    }
    public function publications(int $modality, string $start, string $end): \Generator
    {
        $page = 1;
        do {
            $response = $this->request(config('radar.pncp_url').'/contratacoes/publicacao', [
                    'dataInicial'=>$start, 'dataFinal'=>$end, 'codigoModalidadeContratacao'=>$modality,
                    'pagina'=>$page, 'tamanhoPagina'=>50,
                ]);
            if ($response->status() === 204) return;
            $body = $response->json();
            if (!is_array($body) || !isset($body['data']) || !is_array($body['data']) || !isset($body['totalPaginas'])) throw new RuntimeException('Resposta PNCP inesperada.');
            foreach ($body['data'] as $row) yield $row;
            $total = (int) $body['totalPaginas'];
            $page++;
        } while ($page <= $total);
    }
    public function map(array $row): array
    {
        foreach (['numeroControlePNCP','orgaoEntidade','anoCompra','sequencialCompra','objetoCompra','modalidadeId'] as $key) if (!isset($row[$key])) throw new RuntimeException("PNCP: campo ausente {$key}");
        return [
            'pncp_id'=>$row['numeroControlePNCP'], 'cnpj'=>$row['orgaoEntidade']['cnpj'],
            'year'=>$row['anoCompra'], 'sequence'=>$row['sequencialCompra'], 'object'=>$row['objetoCompra'],
            'agency'=>$row['orgaoEntidade']['razaoSocial'], 'state'=>$row['unidadeOrgao']['ufSigla'] ?? null,
            'city'=>$row['unidadeOrgao']['municipioNome'] ?? null, 'modality_id'=>$row['modalidadeId'],
            'modality'=>$row['modalidadeNome'] ?? 'Não informada', 'estimated_value'=>$row['valorTotalEstimado'] ?? null,
            'published_at'=>$row['dataPublicacaoPncp'] ?? null, 'deadline'=>$row['dataEncerramentoProposta'] ?? null, 'source'=>$row,
        ];
    }

    public function openProposals(int $modality): \Generator
    {
        $page = 1;
        do {
            $response = $this->request(config('radar.pncp_url').'/contratacoes/proposta',[
                'dataFinal'=>now()->addDays(config('radar.proposal_days',365))->format('Ymd'),
                'codigoModalidadeContratacao'=>$modality,'pagina'=>$page,'tamanhoPagina'=>50,
            ]);
            if ($response->status() === 204) return;
            $body = $response->json();
            if (!is_array($body) || !isset($body['data']) || !is_array($body['data']) || !isset($body['totalPaginas'])) throw new RuntimeException('Resposta PNCP inesperada.');
            foreach ($body['data'] as $row) yield $row;
            $page++;
        } while ($page <= (int)$body['totalPaginas']);
    }
}
