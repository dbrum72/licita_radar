<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\{SyncService, DigestService};
class SyncPncp implements ShouldQueue
{
    use Queueable;
    public $timeout = 21000;
    public $tries = 1;
    public function handle(SyncService $sync, DigestService $digest): void
    {
        $run = $sync->run();
        if ($run->status === 'failed') throw new \RuntimeException('Sincronização PNCP falhou.');
        $digest->send();
    }
}
