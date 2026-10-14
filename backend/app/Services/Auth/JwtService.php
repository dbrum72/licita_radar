<?php

namespace App\Services\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

class JwtService
{
    private function key(): string
    {
        $secret = config('jwt.secret');
        if (! $secret) {
            $appKey = config('app.key');
            $appKey = str_starts_with($appKey ?? '', 'base64:') ? base64_decode(substr($appKey, 7), true) : $appKey;
            if (! is_string($appKey) || strlen($appKey) < 32) {
                throw new RuntimeException('Configure uma chave de aplicação válida para autenticação JWT.');
            }
            $secret = hash_hmac('sha256', 'licitaradar-jwt-signing-v1', $appKey, true);
        }
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET deve possuir pelo menos 32 bytes.');
        }

        return $secret;
    }

    public function issue(User $user): array
    {
        $issued = now()->timestamp;
        $expires = $issued + max(1, (int) config('jwt.ttl')) * 60;
        $claims = [
            'iss' => config('jwt.issuer'), 'aud' => config('jwt.audience'),
            'sub' => (string) $user->getKey(), 'iat' => $issued, 'nbf' => $issued,
            'exp' => $expires, 'jti' => (string) Str::uuid(),
        ];
        $token = JWT::encode($claims, $this->key(), 'HS256');
        Cache::put('jwt:'.$claims['jti'], $claims['sub'], now()->setTimestamp($expires));

        return ['user' => $user, 'access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => $expires - $issued];
    }

    public function claims(string $token): object
    {
        $timestamp = JWT::$timestamp;
        try {
            JWT::$timestamp = now()->timestamp;
            $claims = JWT::decode($token, new Key($this->key(), 'HS256'));
        } finally {
            JWT::$timestamp = $timestamp;
        }
        if (($claims->iss ?? null) !== config('jwt.issuer') || ($claims->aud ?? null) !== config('jwt.audience')
            || ! isset($claims->exp, $claims->iat, $claims->nbf, $claims->jti, $claims->sub)
            || ! is_string($claims->jti) || ! is_string($claims->sub)
            || ! ctype_digit($claims->sub) || (int) $claims->sub < 1
            || $claims->exp <= now()->timestamp || $claims->nbf > now()->timestamp
            || Cache::get('jwt:'.$claims->jti) !== $claims->sub) {
            throw new UnexpectedValueException('Token inválido, expirado ou revogado.');
        }

        return $claims;
    }

    public function user(string $token): ?User
    {
        try {
            return User::find($this->claims($token)->sub);
        } catch (UnexpectedValueException|\DomainException|\InvalidArgumentException $exception) {
            return null;
        }
    }

    public function revoke(string $token): void
    {
        Cache::forget('jwt:'.$this->claims($token)->jti);
    }
}
