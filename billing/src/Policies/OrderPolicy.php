<?php

namespace Boy132\Billing\Policies;

use App\Policies\DefaultAdminPolicies;
use Boy132\Billing\Models\Order;
use Illuminate\Contracts\Auth\Authenticatable;

class OrderPolicy
{
    use DefaultAdminPolicies;

    protected string $modelName = 'order';

    /**
     * Allow listing orders for authenticated users (filtered by resource query).
     */
    public function viewAny(Authenticatable $user): bool
    {
        return (bool) $user;
    }

    /**
     * Allow users to view their own orders.
     */
    public function view(Authenticatable $user, Order $order): bool
    {
        if ($order->customer && $order->customer->user_id === $user->getAuthIdentifier()) {
            return true;
        }

        return false;
    }
}
