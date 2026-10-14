<?php

namespace Tests\Feature;

use App\Mail\OpportunityDigest;
use App\Models\SearchProfile;
use Tests\TestCase;

class OpportunityDigestTest extends TestCase
{
    public function test_email_formats_values_deadlines_and_escapes_external_text(): void
    {
        config(['app.timezone' => 'America/Sao_Paulo']);
        $profile = new SearchProfile(['name' => 'Projetos <script>alert(1)</script>']);
        $item = (object) ['object' => 'Projeto <img src=x onerror=alert(1)>', 'agency' => 'Órgão & parceiros',
            'city' => 'Florianópolis', 'state' => 'SC', 'modality' => 'Concorrência', 'estimated_value' => 99399.81,
            'deadline' => '2026-10-14T18:00:00Z', 'cnpj' => '12345678000100', 'year' => 2026, 'sequence' => 7];
        $mail = new OpportunityDigest($profile, collect([$item]), 'https://interface.example.test');
        $html = $mail->render();
        $this->assertStringContainsString('R$ 99.399,81', $html);
        $this->assertStringContainsString('14/10/2026 15:00', $html);
        $this->assertStringContainsString('https://pncp.gov.br/app/editais/12345678000100/2026/7', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertSame('emails.opportunity-digest-text', $mail->textView);
        $text = view($mail->textView, ['profile' => $profile, 'opportunities' => $mail->opportunities, 'frontendUrl' => $mail->frontendUrl])->render();
        $this->assertStringContainsString('Acesse o painel: https://interface.example.test', $text);
        $this->assertStringContainsString('R$ 99.399,81', $text);
        $item->estimated_value = null;
        $this->assertStringContainsString('Não informado', (new OpportunityDigest($profile, collect([$item]), $mail->frontendUrl))->render());
    }
}
