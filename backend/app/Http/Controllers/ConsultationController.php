<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultationRequest;
use App\Services\SyncService;
use Illuminate\Contracts\Cache\LockTimeoutException;

class ConsultationController extends Controller
{
    public function store(ConsultationRequest $request, SyncService $sync)
    {
        set_time_limit(21600);
        try {
            $run = $sync->run($request->validated('profile_ids'));
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Já existe uma consulta em andamento. Aguarde sua conclusão.'], 409);
        }

        return response()->json(['message' => $run->status === 'completed'
            ? "Consulta concluída: {$run->imported} novas oportunidades."
            : $run->error, 'run' => $run], $run->status === 'completed' ? 200 : 502);

    }
}
