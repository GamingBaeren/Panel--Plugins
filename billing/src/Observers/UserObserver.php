<?php

namespace Boy132\Billing\Observers;

use App\Models\User;
use Boy132\Billing\Models\Customer;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create customer automatically when user is created
        Customer::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $user->first_name ?? $user->username,
                'last_name' => $user->last_name ?? $user->username,
                'balance' => 0,
            ]
        );
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Update customer name if user first_name or last_name changed
        if ($user->isDirty(['first_name', 'last_name'])) {
            $customer = Customer::where('user_id', $user->id)->first();
            
            if ($customer) {
                $customer->update([
                    'first_name' => $user->first_name ?? $customer->first_name,
                    'last_name' => $user->last_name ?? $customer->last_name,
                ]);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Delete customer when user is deleted (cascadeOnDelete in migration handles this)
    }
}
