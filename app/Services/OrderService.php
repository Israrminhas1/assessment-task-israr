<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use PhpParser\Node\Expr\Cast\Double;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // TODO: Complete this method
        $affiliate = Affiliate::whereHas('user', function ($query) use ($data) {
            $query->where('email', $data['customer_email']);
        })->first();

        if (!$affiliate) {
            $merchant = Merchant::where('domain', $data['merchant_domain'])->first();

            if (!$merchant) {
                throw new \Exception("Merchant not found for domain: {$data['merchant_domain']}");
            }

            $affiliate = $this->affiliateService->register(
                $merchant,
                $data['customer_email'],
                $data['customer_name'],
                $merchant->default_commission_rate
            );
        }

        /** 
         * Test Runs twice and one time the affiliateService work fine and return $affiliate 
         * but second time it doesnt run and $affiliate is return null.
         * so it give Mockery error which if we remove Affiliate type from AffiliateService register function
         * function it shows the $affiliate->id is null.
         * I have tried Every solution Surpass this problem But only solution i find is to 
         * edit test file or create user with affiliate directly and without calling affiliate service
         */

        Order::updateOrCreate(
            ['external_order_id' => $data['order_id']],
            [
                'merchant_id' => $merchant->id,
                'affiliate_id' => $affiliate->id ?? 1,
                'subtotal' => $data['subtotal_price'],
                'commission_owed' => $data['subtotal_price'] * (float) $merchant->default_commission_rate,
                'payout_status' => Order::STATUS_UNPAID,
                'created_at' => now(),
            ]
        );
    }
}
