<?php

namespace App\Providers;

use App\Integrations\Pncp\Contracts\PncpClient;
use App\Integrations\Pncp\HttpPncpClient;
use App\Models\Opportunity;
use App\Services\Auth\JwtService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PncpClient::class, HttpPncpClient::class);
    }

    public function boot(): void
    {
        Auth::viaRequest('jwt', fn (Request $request) => $request->bearerToken()
            ? app(JwtService::class)->user($request->bearerToken()) : null);
        $response = static function (Request $request, array $headers) {
            $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));

            return response()->json(['message' => "Muitas tentativas. Aguarde {$seconds} segundos e tente novamente.",
                'retry_after' => $seconds], 429, $headers);
        };
        foreach (['pncp-consultation', 'manual-digest'] as $action) {
            RateLimiter::for($action, fn (Request $request) => Limit::perMinutes(5, 1)
                ->by($action.':'.$request->user()->id)->response($response));
        }
        foreach (['auth-login', 'auth-setup'] as $action) {
            RateLimiter::for($action, fn (Request $request) => Limit::perMinute(5)
                ->by($action.':'.$request->ip())->response($response));
        }
        RateLimiter::for('tender-attachments', function (Request $request) {
            $opportunity = $request->route('opportunity');

            return Limit::perMinutes(5, 2)
                ->by($request->user()->id.':'.($opportunity instanceof Opportunity ? $opportunity->getKey() : $opportunity))
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Busca de anexos solicitada recentemente para esta oportunidade. Aguarde '.($headers['Retry-After'] ?? 300).' segundos antes de tentar novamente.',
                    'retry_after' => (int) ($headers['Retry-After'] ?? 300),
                ], 429, $headers));
        });
    }
}
