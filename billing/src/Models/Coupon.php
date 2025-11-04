<?php

namespace Boy132\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Stripe\StripeClient;

/**
 * @property int $id
 * @property ?string $stripe_id
 * @property string $name
 * @property string $code
 * @property ?int $amount_off
 * @property ?int $percent_off
 * @property ?int $max_redemptions
 * @property ?Carbon $redeem_by
 */
class Coupon extends Model
{
    protected $fillable = [
        'stripe_id',
        'name',
        'code',
        'amount_off',
        'percent_off',
        'max_redemptions',
        'redeem_by',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->code ??= Str::random(8);
        });

        static::created(function (self $model) {
            $model->sync();
        });

        static::updating(function (self $model) {
            $model->code ??= Str::random(8);
        });

        static::updated(function (self $model) {
            $model->sync();
        });

        static::deleted(function (self $model) {
            if (!is_null($model->stripe_id)) {
                /** @var StripeClient $stripeClient */
                $stripeClient = app(StripeClient::class); // @phpstan-ignore myCustomRules.forbiddenGlobalFunctions

                $stripeClient->coupons->delete($model->stripe_id);
            }
        });
    }

    public function sync(): void
    {
        /** @var StripeClient $stripeClient */
        $stripeClient = app(StripeClient::class); // @phpstan-ignore myCustomRules.forbiddenGlobalFunctions

        if (is_null($this->stripe_id)) {
            $data = [
                'name' => $this->name,
                'id' => $this->code,
            ];

            if ($this->amount_off) {
                $data['currency'] = config('billing.currency');
                $data['amount_off'] = $this->amount_off;
            }

            if ($this->percent_off) {
                $data['percent_off'] = $this->percent_off;
            }

            if ($this->max_redemptions) {
                $data['max_redemptions'] = $this->max_redemptions;
            }

            if ($this->redeem_by) {
                $data['redeem_by'] = $this->redeem_by->timestamp;
            }

            $stripeCoupon = $stripeClient->coupons->create($data);

            $this->updateQuietly([
                'stripe_id' => $stripeCoupon->id,
            ]);
        } else {
            $stripeCoupon = $stripeClient->coupons->retrieve($this->stripe_id);

            // You can't update coupon objects on stripe, so check for changes and recreate the coupon if needed
            if ($stripeCoupon->amount_off !== $this->amount_off || $stripeCoupon->percent_off !== $this->percent_off || $stripeCoupon->max_redemptions !== $this->max_redemptions || $stripeCoupon->redeem_by !== $this->redeem_by) {
                $stripeClient->coupons->delete($this->stripe_id);

                $this->stripe_id = null;
                $this->sync();
            }
        }
    }
}
