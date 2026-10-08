<?php

namespace App\Http\Controllers;

use App\Services\DigestService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;

class DigestController extends Controller
{
    public function store(DigestService $service): JsonResponse
    {
        set_time_limit(3600);
        if (in_array(config('mail.default'), ['log', 'array'])) {
            return response()->json(['message' => 'Configure o SMTP antes de enviar os resumos.'], 422);
        }

        try {
            $sent = $service->send();
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Já existe um envio em andamento. Aguarde sua conclusão.'], 409);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Não foi possível concluir o envio dos resumos. Verifique o SMTP e tente novamente.'], 502);
        }

        return response()->json(['sent' => $sent, 'message' => $sent
            ? "{$sent} resumos enviados por e-mail."
            : 'Nenhuma oportunidade nova para enviar aos perfis ativos com e-mail cadastrado.']);
    }
}
