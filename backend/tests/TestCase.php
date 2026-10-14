<?php

namespace Tests;

use App\Models\CompanyDocument;
use App\Services\Auth\JwtService;
use Dompdf\Dompdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function createCompanyDocument(array $attributes): CompanyDocument
    {
        $pages = $attributes['pages'] ?? [];
        unset($attributes['pages']);
        $attributes['path'] = 'fixtures/'.Str::uuid().'.pdf';
        $document = CompanyDocument::create($attributes);
        $this->writeCompanyPdf($document, $pages);

        return $document;
    }

    protected function writeCompanyPdf(CompanyDocument $document, array $pages): void
    {
        $html = '<meta charset="utf-8"><style>body { font-family: DejaVu Sans; } div { page-break-after: always; } div:last-child { page-break-after: auto; }</style>';
        $last = $pages ? max(array_column($pages, 'number')) : 1;
        for ($number = 1; $number <= $last; $number++) {
            $page = collect($pages)->firstWhere('number', $number);
            $html .= '<div>'.htmlspecialchars($page['text'] ?? '', ENT_QUOTES, 'UTF-8').'</div>';
        }
        $pdf = new Dompdf;
        $pdf->loadHtml($html);
        $pdf->render();
        Storage::disk('local')->put($document->getRawOriginal('path'), $pdf->output());
    }

    public function actingAs(Authenticatable $user, $guard = null)
    {
        return $this->withToken(app(JwtService::class)->issue($user)['access_token']);
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        // Each test request must resolve its bearer token, like a fresh HTTP request.
        Auth::forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
