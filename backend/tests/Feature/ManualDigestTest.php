<?php

namespace Tests\Feature;

use App\Mail\OpportunityDigest;
use App\Models\Opportunity;
use App\Models\SearchProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManualDigestTest extends TestCase
{
    use RefreshDatabase;

    private function notification(): void
    {
        $this->createCompanyDocument(['name' => 'acervo.pdf', 'kind' => 'rrt', 'path' => 'test.pdf', 'sha256' => str_repeat('f', 64),
            'status' => 'ready', 'pages' => [['number' => 1, 'text' => 'Registro de elaboração de projetos de arquitetura para edificações.']]]);
        $opportunity = Opportunity::create(['pncp_id' => 'manual-2026-1', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 1,
            'object' => 'Elaboração de projeto arquitetônico', 'agency' => 'Prefeitura', 'modality_id' => 4, 'modality' => 'Concorrência',
            'scope_reason' => 'Projeto', 'scope_status' => 'relevant', 'deadline' => now()->addDays(20), 'source' => []]);
        $profile = SearchProfile::create(['name' => 'Arquitetura', 'keywords' => [], 'min_days' => 0, 'active' => true, 'email' => 'test@example.test']);
        DB::table('profile_opportunity')->insert(['search_profile_id' => $profile->id, 'opportunity_id' => $opportunity->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_manual_digest_requires_authentication_and_configured_smtp(): void
    {
        Mail::fake();
        $this->postJson('/api/digest')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson('/api/digest')->assertUnprocessable();
        Mail::assertNothingSent();
    }

    public function test_manual_digest_sends_once_and_marks_only_delivered_results(): void
    {
        config(['mail.default' => 'smtp', 'cors.allowed_origins' => ['https://interface.example.test'], 'app.url' => 'https://api.example.test']);
        Mail::fake();
        $this->notification();
        $this->actingAs(User::factory()->create())->postJson('/api/digest')->assertOk()->assertJsonPath('sent', 1);
        Mail::assertSent(OpportunityDigest::class, function ($mail) {
            $html = $mail->render();
            $this->assertStringContainsString('https://interface.example.test', $html);
            $this->assertStringContainsString('Ver edital no PNCP', $html);
            $this->assertStringContainsString('Elaboração de projeto arquitetônico', $html);

            return $mail->hasTo('test@example.test');
        });
        $this->assertNotNull(DB::table('profile_opportunity')->first()->notified_at);
        $this->travel(6)->minutes();
        $this->postJson('/api/digest')->assertOk()->assertJsonPath('sent', 0);
        Mail::assertSentCount(1);
    }

    public function test_smtp_failure_returns_error_and_preserves_pending_notification(): void
    {
        config(['mail.default' => 'smtp']);
        $this->notification();
        Mail::shouldReceive('to')->once()->with('test@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->actingAs(User::factory()->create())->postJson('/api/digest')->assertStatus(502);
        $this->assertNull(DB::table('profile_opportunity')->first()->notified_at);
    }
}
