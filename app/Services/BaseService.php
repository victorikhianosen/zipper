<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Exceptions\CustomException;
use App\Models\BookFlight;
use App\Models\Transaction;
use App\Traits\GetsPagination;

class BaseService
{
    use GetsPagination;

    protected function calculatePaystackCharge(float $amount): float
    {
        if ($amount <= 2499) {
            $finalAmount = ($amount / 0.985) + 0.01;
        } else {
            $amountFlatFee = $amount + 100;
            $finalAmount = ($amountFlatFee / 0.985) + 0.01;
        }

        return ceil($finalAmount);
    }

    protected function calculateFlutterwaveCharge(float $amount): float
    {
        return ceil($amount * 1.4 / 100);
    }

    protected function verifyFlightPayment(string $offerId, string $reference): Transaction
    {
        /** @var \App\Models\Transaction $trxn */
        $trxn = Transaction::whereOfferId($offerId)->whereReference($reference)->first();
        /** @var \App\Models\BookFlight $bookedFlight */
        $bookedFlight = BookFlight::whereOfferId($offerId)->whereUserId($trxn->user_id)->first();
        throw_if(blank($trxn), CustomException::class, 'Payment not found');
        if ($bookedFlight && ($bookedFlight->payment_status == TransactionStatus::SUCCESS->value)) {
            throw_if(filled($bookedFlight), CustomException::class, 'Flight already booked');
        }

        return $trxn;
    }
}
