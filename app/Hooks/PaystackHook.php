<?php

namespace App\Hooks;

use App\Enums\TransactionStatus;
use App\Http\Factories\TransactionFactory;
use App\Models\BookFlight;
use App\Models\Transaction;
use App\Models\User;

class PaystackHook
{
    public function processHook(): void
    {
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
            exit;
        }

        $secretKey = config('vars.paystack.sec_key');
        $input = @file_get_contents('php://input');
        $context = ['request' => request()->all(), 'headers' => request()->headers];
        http_response_code(200);
        $event = request()->all();
        logger()->info($context); //@phpstan-ignore-line
        if (@$_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, $secretKey)) { //@phpstan-ignore-line
            exit;
        }

        if ($event['event'] != 'charge.success' || ! $event['data']['metadata']) {
            exit;
        }
        if ($event['data']['metadata']['type'] == 'flight') {

            $this->processFlightBookingPayment($event);
        }
    }

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
            'success' => TransactionStatus::SUCCESS->value,
            'failed' => TransactionStatus::FAILED->value,
            'pending' => TransactionStatus::PENDING->value,
            default => TransactionStatus::PENDING->value
        };

        $user = User::whereEmail($payerEmail)->first();
        /** @var \App\Models\Transaction $trxn */
        $trxn = Transaction::whereUserId($user?->id)->whereReference($trxRef)->whereOfferId($assocOfferId)->first();
        $flight = BookFlight::whereId($trxn->booking_flight_id)->first(); //@phpstan-ignore-line
        if (blank($user) || blank($trxn) || $trxn->status != 'pending') {
            exit;
        }
        logger($trxn);

        logger($flight);
        $trxn->update(['status' => strval($presumedTrxnStatus), 'raw_response' => $event, 'meta' => $meta]);
        $flight->update(['payment_status' => strval($presumedTrxnStatus)]); //@phpstan-ignore-line
        $user->depositPoint(getPointBonus($trxn->amount));
        logger('gothere');
        TransactionFactory::createPointBonusTransaction(getPointBonus($trxn->amount));
    }
}
