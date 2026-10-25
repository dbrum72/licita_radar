<?php

namespace App\Integrations\Pncp;

use App\Integrations\Pncp\Contracts\PncpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpPncpClient implements PncpClient
{
    public function request(string $url, array $query = [], array $options = []): Response
    {
        // Pace requests and honor rate limits. Only trusted, fixed PNCP URLs are used.
        for ($attempt = 0; $attempt < 4; $attempt++) {
            if (! app()->environment('testing')) {
                usleep((int) config('radar.request_delay_ms') * 1000);
            }
            try {
                $response = Http::acceptJson()->withOptions(['allow_redirects' => false, ...$options])
                    ->connectTimeout($options['connect_timeout'] ?? 10)->timeout($options['timeout'] ?? 60)->get($url, $query);
            } catch (ConnectionException $exception) {
                if ($attempt === 3) {
                    throw $exception;
                }
                if (! app()->environment('testing')) {
                    sleep(5 * ($attempt + 1));
                }

                continue;
            }
            if ($response->status() !== 429 && ! $response->serverError()) {
                return $response->throw();
            }
            if ($attempt === 3) {
                return $response->throw();
            }
            $retryAfter = $response->header('Retry-After');
            $wait = is_numeric($retryAfter) ? (int) $retryAfter : max(0, (strtotime($retryAfter ?: '') ?: time()) - time());
            if (! app()->environment('testing')) {
                sleep(min(60, max($wait, 15 * (2 ** $attempt))));
            }
        }
        throw new RuntimeException('Falha ao consultar PNCP.');
    }

    public function openProposals(int $modality): \Generator
    {
        $page = 1;
        do {
            $response = $this->request(config('radar.pncp_url').'/contratacoes/proposta', [
                'dataFinal' => now()->addDays(config('radar.proposal_days', 365))->format('Ymd'),
                'codigoModalidadeContratacao' => $modality, 'pagina' => $page, 'tamanhoPagina' => 50,
            ]);
            if ($response->status() === 204) {
                return;
            }
            $body = $response->json();
            if (! is_array($body) || ! isset($body['data']) || ! is_array($body['data']) || ! array_is_list($body['data'])
                || ! isset($body['totalPaginas']) || filter_var($body['totalPaginas'], FILTER_VALIDATE_INT) === false || (int) $body['totalPaginas'] < 0) {
                throw new RuntimeException('Resposta PNCP inesperada.');
            }
            foreach ($body['data'] as $row) {
                if (! is_array($row)) {
                    throw new RuntimeException('Item PNCP inesperado.');
                }
                yield $row;
            }
            $page++;
        } while ($page <= (int) $body['totalPaginas']);
    }
}
