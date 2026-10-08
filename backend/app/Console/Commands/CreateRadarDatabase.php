<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class CreateRadarDatabase extends Command
{
    protected $signature = 'radar:database {--testing : Cria somente o banco isolado dos testes}';

    protected $description = 'Cria a base MySQL LicitaRadar com as credenciais configuradas';

    public function handle(): int
    {
        $config = config('database.connections.mysql');
        $name = $this->option('testing') ? 'licitaRadar_test_bd' : 'licitaRadar_bd';
        try {
            $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("Banco MySQL {$name} pronto.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Não foi possível criar o banco MySQL. Verifique host, porta, usuário, senha e permissão CREATE no .env.');

            return self::FAILURE;
        }
    }
}
