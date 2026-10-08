<?php

namespace Tests\Feature;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Integrations\Pncp\HttpPncpClient;
use App\Integrations\Pncp\PncpOpportunityMapper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LegalisPracticesTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_reject_invalid_states_dates_and_value_ranges(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/opportunities?state=XX')->assertUnprocessable()->assertJsonValidationErrors('state');
        $this->getJson('/api/opportunities?deadline=10-08-2026')->assertUnprocessable()->assertJsonValidationErrors('deadline');
        $this->getJson('/api/opportunities?min_value=200&max_value=100')->assertUnprocessable()->assertJsonValidationErrors('max_value');
        $this->getJson('/api/opportunities?min_value=0&max_value=0&state=SP')->assertOk();
    }

    public function test_consultation_and_digest_do_not_share_rate_limit_counters(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create());
        // No acervo: the consultation fails but still uses its own request budget.
        $this->postJson('/api/sync', ['profile_ids' => []])->assertStatus(502);
        $this->postJson('/api/digest')->assertUnprocessable();
        $this->postJson('/api/sync', ['profile_ids' => []])->assertStatus(429)->assertJsonStructure(['message', 'retry_after']);
        Http::assertNothingSent();
    }

    public function test_integration_contract_resolves_to_http_client_and_rejects_malformed_pages(): void
    {
        $this->assertInstanceOf(HttpPncpClient::class, app(PncpClient::class));
        Http::fake(['*' => Http::response(['data' => ['bad-row'], 'totalPaginas' => 1])]);
        $this->expectException(\RuntimeException::class);
        iterator_to_array(app(PncpClient::class)->openProposals(4));
    }

    public function test_mapper_rejects_incomplete_organizations_instead_of_accessing_missing_keys(): void
    {
        $this->expectException(\RuntimeException::class);
        app(PncpOpportunityMapper::class)->map(['orgaoEntidade' => ['cnpj' => '12345678000100']]);
    }
}
