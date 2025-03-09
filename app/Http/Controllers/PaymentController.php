<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;  // Correct import for request handling
use App\Http\Requests\PaymentRequest;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}


    public function makePayment(): JsonResponse
    {
        $data = $this->paymentService->makePayment();

        return $this->success(data: $data);
    }


    public function makePaymentDeal(PaymentRequest $request)
    {
        $validated = $request->validated();
        $data = $this->paymentService->makeDealPayment($validated);
        return $this->success(data: $data);
    }


    // public function makePaymentDeal(Request $request)
    // {
    //     $validated = $request->validate([
    //         'channel' => 'required|in:flutterwave',
    //         'deal_id' => 'required|uuid',
    //         'booking_id' => 'required|uuid',
    //     ]);

    //     $data = $this->paymentService->makeDealPayment($validated);

    //     return $this->success(data: $data);

    // }


    // public function makePayment(): JsonResponse
    // {
    //     $data = $this->paymentService->makePayment();

    //     return $this->success(data: $data);
    // }


    public function paystackCallback(Request $request) {
        return $request->all();
    }
}
