<?php

namespace App\Services;

use App\Enums\Status\FlightStatus;
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
            $finalAmount = floatval(($amount / 0.985) + 0.01);

            return ceil(floatval($finalAmount));
        } else {
            $amountFlatFee = $amount + 100;
            $finalAmount = floatval(($amountFlatFee / 0.985) + 0.01);

            return ceil(floatval($finalAmount));
        }
    }

    protected function verifyFlightPayment(string $offerId, string $reference): Transaction
    {
        /** @var \App\Models\Transaction $trxn */
        $trxn = Transaction::whereOfferId($offerId)->whereReference($reference)->first();
        /** @var \App\Models\BookFlight $bookedFlight */
        $bookedFlight = BookFlight::whereOfferId($offerId)->first();
        throw_if(blank($trxn), CustomException::class, 'Payment not found');
        if ($bookedFlight->status != FlightStatus::TRXFAILED->value || $bookedFlight->status != FlightStatus::PENDING->value) {
            throw_if(filled($bookedFlight), CustomException::class, 'Flight already booked');
        }

        return $trxn;
    }
}
