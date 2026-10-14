<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SetupRequest;
use App\Models\User;
use App\Services\Auth\AuthenticateUser;
use App\Services\Auth\JwtService;
use App\Services\Registration\RegisterAdministrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json(['setup' => ! User::exists()]);
    }

    public function setup(SetupRequest $request, RegisterAdministrator $registrar): JsonResponse
    {
        return response()->json($registrar->execute($request->validated()), 201);
    }

    public function login(LoginRequest $request, AuthenticateUser $authenticator): JsonResponse
    {
        return response()->json($authenticator->execute($request->validated()));
    }

    public function logout(Request $request, JwtService $tokens): Response
    {
        $tokens->revoke($request->bearerToken());

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
