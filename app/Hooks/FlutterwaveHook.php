<?php

namespace App\Hooks;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\FlightService;
use App\Traits\ProcessFlightBookingPayment;
use Illuminate\Support\Arr;

class FlutterwaveHook
{
    use ProcessFlightBookingPayment;

    public function __construct(protected FlightService $flightService)
    {
    }

    public function processHook(): void
    {
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
            exit;
        }
        $context = ['request' => request()->all(), 'headers' => request()->headers];
        http_response_code(200);
        $event = request()->all();
        logger()->info(json_encode($context)); //@phpstan-ignore-line
        $hash = request()->header('verif-hash');
        if (! $hash || ($hash !== config('vars.flutterwave.sec_hash'))) {
            logger('signature mismatch');
            exit;
        }
        if (Arr::get($event, 'event') != 'charge.completed') {
            exit;
        }
        $transaction = Transaction::query()
            ->where('reference', Arr::get($event['data'], 'tx_ref'))
            ->where('status', TransactionStatus::PENDING->value)
            ->first();
        if (! $transaction || ! Arr::get($transaction, 'meta')) {
            exit;
        }
        $event['data']['metadata'] = $transaction['meta'];
        if ($event['data']['metadata']['type'] == 'flight') {
            $this->processFlightBookingPayment($event);
        }
    }
}
