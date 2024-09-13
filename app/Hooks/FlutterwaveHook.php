<?php

namespace App\Hooks;

use App\Traits\ProcessFlightBookingPayment;

class FlutterwaveHook
{
    use ProcessFlightBookingPayment;

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
        if ($event['event'] != 'charge.completed' || ! $event['data']['metadata']) {
            exit;
        }
        $event['data']['metadata'] = $event['meta']['data'];
        if ($event['data']['metadata']['type'] == 'flight') {
            $this->processFlightBookingPayment($event);
        }
    }
}
