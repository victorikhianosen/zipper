<?php

class PaystackHook
{
    public function processHook(): void
    {
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
            exit;
        }

        $secretKey = config('vars.paystack.sec_key');
        $input = @file_get_contents('php://input');
        $context = ['request' => request(), 'headers' => request()->headers];
        http_response_code(200);
        $event = request()->all();
        logger()->info($context); //@phpstan-ignore-line
        if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, $secretKey)) { //@phpstan-ignore-line
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
    protected function processFlightBookingPayment($event): void
    {

    }
}
