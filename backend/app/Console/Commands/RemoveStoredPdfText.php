<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveStoredPdfText extends Command
{
    protected $signature = 'radar:remove-pdf-text';

    protected $description = 'Remove texto persistido dos PDFs e o antigo fluxo de requisitos, preservando arquivos e cadastros';

    public function handle(): int
    {
        $lock = Cache::lock('pncp-sync', 3600);
        if (! $lock->get()) {
            $this->error('Aguarde a consulta PNCP terminar antes da limpeza.');

            return self::FAILURE;
        }
        try {
            if (DB::table('opportunities')->where('attachments_status', 'running')->exists()) {
                $this->error('Aguarde a busca de anexos terminar antes da limpeza.');

                return self::FAILURE;
            }
            $requirements = Schema::hasTable('requirements') ? DB::table('requirements')->count() : 0;
            Schema::dropIfExists('requirements');
            DB::table('migrations')->where('migration', '2026_10_06_000006_create_requirements_table')->delete();
            foreach (['documents', 'company_documents'] as $table) {
                if (Schema::hasColumn($table, 'pages')) {
                    DB::statement("ALTER TABLE `{$table}` DROP COLUMN `pages`");
                }
                DB::statement("ALTER TABLE `{$table}` ALTER COLUMN `status` SET DEFAULT 'stored'");
                DB::table($table)->update(['status' => 'stored', 'error' => null]);
            }
            DB::table('opportunities')->where('attachments_error', 'like', 'Há PDFs com extração%')
                ->update(['attachments_status' => 'ready', 'attachments_error' => null]);
            $this->info("Texto dos PDFs removido; {$requirements} requisitos excluídos. Arquivos e cadastros preservados.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
