<?php

namespace Boy132\Tickets\Providers;

use App\Models\Role;
use Illuminate\Support\ServiceProvider;

class TicketsPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        Role::registerCustomDefaultPermissions('ticket');
        Role::registerCustomModelIcon('ticket', 'tabler-ticket');
    }

    public function boot(): void {}
}
