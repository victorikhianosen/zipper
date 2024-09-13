<?php

namespace App\Traits;

use App\Enums\TransactionStatus;
use App\Http\Factories\TransactionFactory;
use App\Models\BookFlight;
use App\Models\Transaction;
use App\Models\User;

trait ProcessFlightBookingPayment
{
    /**
     * @param  array<string,mixed>  $event
     */
    protected function processFlightBookingPayment(array $event): void
    {
        $meta = $event['data']['metadata'];
        $trxRef = $meta['reference'];
        $payerEmail = $meta['email'];
        $trxRecordedAmount = $meta['amount'];
        $assocOfferId = $meta['offer_id'];
        $gatewayStatus = $event['data']['status'];
        $presumedTrxnStatus = match ($gatewayStatus) {
            'successful' => TransactionStatus::SUCCESS->value,
            'failed' => TransactionStatus::FAILED->value,
            default => TransactionStatus::PENDING->value
        };

        $user = User::whereEmail($payerEmail)->first();
        /** @var \App\Models\Transaction $trxn */
        $trxn = Transaction::whereUserId($user?->id)->whereReference($trxRef)->whereOfferId($assocOfferId)->first();
        $flight = BookFlight::whereId($trxn->book_flight_id)->first();
        if (blank($user) || blank($trxn) || $trxn->status != 'pending') {
            exit;
        }
        logger($trxn);
        logger($flight);
        $trxn->update(['status' => strval($presumedTrxnStatus), 'raw_response' => $event, 'meta' => $meta]);
        $flight->update(['payment_status' => strval($presumedTrxnStatus)]); //@phpstan-ignore-line
        $user->depositPoint(getPointBonus($trxn->amount));

        TransactionFactory::createPointBonusTransaction(getPointBonus($trxn->amount));
    }
}
