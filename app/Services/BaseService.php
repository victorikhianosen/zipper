<?php

namespace App\Services;

use App\Traits\GetsPagination;

class BaseService
{
    use GetsPagination;

    protected function calculatePaystackCharge(float $amount): float
    {
        if ($amount <= 2499) {
            $finalAmount = floatval(($amount / 0.985) + 0.01);

            return ceil(floatval($finalAmount));
        } else {
            $amountFlatFee = $amount + 100;
            $finalAmount = floatval(($amountFlatFee / 0.985) + 0.01);

            return ceil(floatval($finalAmount));
        }
    }
}
