<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private JwtService $tokens) {}

    public function status()
    {
        return response()->json(['setup' => ! User::exists()]);
    }

    public function setup(Request $request)
    {
        if (User::exists()) {
            abort(403, 'A configuração inicial já foi concluída.');
        }
        if (! in_array($request->ip(), ['127.0.0.1', '::1'])) {
            abort(403, 'Crie o primeiro usuário pelo comando radar:user no servidor.');
        }
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:12|max:128']);

        return Cache::lock('radar-setup', 30)->block(2, function () use ($data) {
            if (User::exists()) {
                abort(403);
            }

            return response()->json($this->tokens->issue(User::create($data)), 201);
        });
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:128']);
        $user = User::where('email', $data['email'])->first();
        $comparisonHash = $user?->password ?? Cache::rememberForever('auth:dummy-password:'.config('hashing.driver'),
            fn () => Hash::make(Str::random(32)));
        if (! Hash::check($data['password'], $comparisonHash) || ! $user) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        return response()->json($this->tokens->issue($user));
    }

    public function logout(Request $request)
    {
        $this->tokens->revoke($request->bearerToken());

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return $request->user();
    }
}
