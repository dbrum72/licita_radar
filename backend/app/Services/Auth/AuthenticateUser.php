<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function __construct(private readonly JwtService $tokens) {}

    public function execute(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();
        $comparisonHash = $user?->password ?? Cache::rememberForever('auth:dummy-password:'.config('hashing.driver'),
            fn () => Hash::make(Str::random(32)));
        if (! Hash::check($credentials['password'], $comparisonHash) || ! $user) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        return $this->tokens->issue($user);
    }
}
