<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {

    }

    public function makePayment(): JsonResponse
    {
        $data = $this->paymentService->makePayment();

        return $this->success(data: $data);
    }
}
