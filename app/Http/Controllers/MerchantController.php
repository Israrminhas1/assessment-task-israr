<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        // TODO: Complete this method
        $dateRange = [
            'from' => Carbon::parse($request->get('from')),
            'to' => Carbon::parse($request->get('to')),
        ];

        $orders = Order::whereBetween('created_at', array_values($dateRange))->get();

        $count = $orders->count();
        $revenue = $orders->sum('subtotal');
        $totalCommissions = $orders->where('payout_status', Order::STATUS_UNPAID)->sum('commission_owed');
        $commissionsOwedByNone = $orders->whereNull('affiliate_id')->where('payout_status', Order::STATUS_UNPAID)->sum('commission_owed');

        $responseData = [
            "count" => $count,
            "revenue" => $revenue,
            "commissions_owed" => $totalCommissions - $commissionsOwedByNone,
        ];

        return response()->json($responseData);
    }
}
