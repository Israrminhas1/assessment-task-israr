<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate)
    {
        // TODO: Complete this method

        $existingMerchant = Merchant::where('user_id', function ($query) use ($email) {
            $query->select('id')->from('users')->where('email', $email);
        })->first();

        $existingAffiliate = Affiliate::where('user_id', function ($query) use ($email) {
            $query->select('id')->from('users')->where('email', $email);
        })->first();

        if ($existingMerchant || $existingAffiliate) {

            throw new AffiliateCreateException("Email already exists as a Merchant or Affiliate.");
        }

        $userArr = [
            "name" => $name,
            "email" => $email,
            "type" => user::TYPE_AFFILIATE
        ];


        $user = User::create($userArr);

        $discount_code = $this->apiService->createDiscountCode($merchant);

        $affiliate = new Affiliate;
        $affiliate->user_id = $user->id;
        $affiliate->merchant_id = $merchant->id;
        $affiliate->commission_rate = $commissionRate;
        $affiliate->discount_code  = $discount_code["code"];
        $affiliate->save();

        Mail::fake();
        Mail::to($user->email)->send(new AffiliateCreated($affiliate));
        return $affiliate;
    }
}
