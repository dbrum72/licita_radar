<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateRadarUser extends Command
{
    protected $signature = 'radar:user {email} {--name=Administrador}';

    protected $description = 'Cria um usuário local do radar.';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('E-mail inválido.');

            return 1;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('Usuário já existe.');

            return 1;
        }
        $password = $this->secret('Senha (mínimo 12 caracteres)');
        if (strlen($password ?? '') < 12) {
            $this->error('Senha curta.');

            return 1;
        }
        User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
        $this->info('Usuário criado.');

        return 0;
    }
}
