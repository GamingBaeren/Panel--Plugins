<?php

namespace Boy132\Subdomains\Providers;

use App\Models\Role;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class SubdomainsPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        Role::registerCustomDefaultPermissions('cloudflare_domain');
        Role::registerCustomModelIcon('cloudflare_domain', 'tabler-world-www');
    }

    public function boot(): void
    {
        Http::macro(
            'cloudflare',
            fn () => Http::acceptJson()
                ->withToken(config('subdomains.token'))
                ->timeout(5)
                ->connectTimeout(1)
                ->baseUrl('https://api.cloudflare.com/client/v4/')
                ->throw()
        );
    }
}
