<?php

namespace App\Hooks;

use App\Enums\TransactionStatus;
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
        logger()->warning('method called');
        $meta = $event['data']['metadata'];
        $trxRef = $meta['reference'];
        $payerEmail = $meta['email'];
        $trxRecordedAmount = $meta['amount'];
        $assocOfferId = $meta['offer_id'];
        $gatewayStatus = $event['data']['status'];
        $presumedTrxnStatus = $gatewayStatus == 'success' ? TransactionStatus::SUCCESS->value : TransactionStatus::FAILED->value;

        $user = User::whereEmail($payerEmail)->first();
        logger($user);
        /** @var \App\Models\Transaction $trxn */
        $trxn = Transaction::whereUserId($user?->id)->whereReference($trxRef)->whereOfferId($assocOfferId)->first();
        logger($trxn->status);
        if (blank($user) || blank($trxn) || $trxn->status != 'pending') {
            exit;
        }
        $trxn->update(['status' => $presumedTrxnStatus, 'raw_response' => $event, 'meta' => $meta]);
    }
}
