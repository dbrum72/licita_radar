<?php

namespace App\Services\Registration;

use App\Models\User;
use App\Services\Auth\JwtService;
use Illuminate\Support\Facades\Cache;

class RegisterAdministrator
{
    public function __construct(private readonly JwtService $tokens) {}

    public function execute(array $data): array
    {
        return Cache::lock('radar-setup', 30)->block(2, function () use ($data) {
            abort_if(User::exists(), 403);

            return $this->tokens->issue(User::create($data));
        });
    }
}
