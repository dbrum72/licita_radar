<?php

namespace Tests\Feature;

use App\Models\CompanyDocument;
use App\Models\User;
use App\Services\Auth\JwtService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_bearer_access_and_logout_revocation_without_session_or_csrf(): void
    {
        $user = User::factory()->create(['password' => 'TestingPassword123!']);
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'TestingPassword123!'])
            ->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('expires_in', 3600)
            ->assertJsonMissingPath('user.password')->assertJsonMissingPath('token');
        $this->assertFalse($response->headers->has('Set-Cookie'));
        $token = $response->json('access_token');
        $this->assertCount(3, explode('.', $token));
        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('id', $user->id);
        $this->postJson('/api/logout')->assertNoContent();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_invalid_signature_missing_token_expiry_and_wrong_audience_are_rejected(): void
    {
        config(['jwt.secret' => str_repeat('s', 64)]);
        $user = User::factory()->create();
        $this->getJson('/api/me')->assertUnauthorized();
        $token = app(JwtService::class)->issue($user)['access_token'];
        $parts = explode('.', $token);
        $parts[2][0] = $parts[2][0] === 'a' ? 'b' : 'a';
        $this->withToken(implode('.', $parts))->getJson('/api/me')->assertUnauthorized();
        $claims = app(JwtService::class)->claims($token);
        $wrongAlgorithm = JWT::encode((array) $claims, str_repeat('s', 64), 'HS512');
        $this->withToken($wrongAlgorithm)->getJson('/api/me')->assertUnauthorized();
        $claims->aud = 'another-application';
        $wrongAudience = JWT::encode((array) $claims, str_repeat('s', 64), 'HS256');
        $this->withToken($wrongAudience)->getJson('/api/me')->assertUnauthorized();
        $this->travel(61)->minutes();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->travelBack();
        $claims->aud = config('jwt.audience');
        $claims->nbf = now()->addMinute()->timestamp;
        $futureToken = JWT::encode((array) $claims, str_repeat('s', 64), 'HS256');
        $this->withToken($futureToken)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_deleted_users_and_cleared_token_registry_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = app(JwtService::class)->issue($user)['access_token'];
        $user->delete();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $token = app(JwtService::class)->issue(User::factory()->create())['access_token'];
        Cache::flush();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_invalid_credentials_and_cross_origin_preflight(): void
    {
        $user = User::factory()->create(['password' => 'TestingPassword123!']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->options('/api/login', [], ['Origin' => 'http://127.0.0.1:5173', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'authorization,content-type'])
            ->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
        $response = $this->options('/api/login', [], ['Origin' => 'https://untrusted.example', 'Access-Control-Request-Method' => 'POST']);
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_unknown_email_returns_the_same_validation_error_as_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'TestingPassword123!']);
        $wrongPassword = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $unknownEmail = $this->postJson('/api/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email')->assertJsonMissingPath('access_token');
        $this->assertSame($wrongPassword->json('errors.email'), $unknownEmail->json('errors.email'));
        $this->postJson('/api/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_private_download_requires_bearer_and_returns_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('company/test.pdf', '%PDF-test');
        $document = CompanyDocument::create(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'company/test.pdf', 'sha256' => str_repeat('a', 64), 'status' => 'ready']);
        $this->get('/api/documents/'.$document->id.'/download', ['Accept' => 'application/json'])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->get('/api/documents/'.$document->id.'/download', ['Accept' => 'application/json'])
            ->assertOk()->assertDownload('acervo.pdf')->assertStreamedContent('%PDF-test');
    }
}
