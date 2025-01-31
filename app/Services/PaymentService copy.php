<?php

namespace App\Services;

use App\Enums\Gateway;
use App\Models\Setting;
use App\Enums\ServiceType;
use App\Models\FlightDeals;
use App\Models\Transaction;
use App\Models\FlightOffers;
use App\Enums\TransactionCat;
use App\Enums\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Enums\TransactionStatus;
use App\Exceptions\CustomException;

class PaymentService extends BaseService
{
    public function __construct(
        protected Transaction $transaction,
        protected FlightOffers $flightOffers,
        protected Setting $setting
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function makePayment(): array
    {
        $messages = ['booking_id.required' => 'Booking is required'];
        $data = request()->validate([
            'channel' => 'bail|required|' . Rule::in(['paystack', 'flutterwave']),
            'offer_id' => 'bail|required|exists:flight_offers,id',
            'booking_id' => 'bail|required|exists:book_flights,id',
        ], $messages);
        $channel = $data['channel'];

        return match ($channel) {
            'paystack' => $this->initiateFlightPaymentWithPaystack($data),
            'flutterwave' => $this->initiateFlightPaymentWithFlutterwave($data),
            default => throw new CustomException('Payment method not valid')
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function initiateFlightPaymentWithPaystack(array $data): array
    {
        $trxn = $this->validateDataAndReturnTransaction($data, Gateway::PAYSTACK->value);
        $user = $trxn->user;
        $offer = $trxn->offer;

        return [
            'reference' => $trxn->reference,
            'amount' => $this->calculatePaystackCharge($trxn->amount) * 100,
            'break_down' => [
                'flight_amount' => $trxn->amount - $this->getFlightServiceCharge(),
                'service_charge' => $this->getFlightServiceCharge(),
                'gateway_charge' => $this->calculatePaystackCharge($trxn->amount) - $trxn->amount,
                'total_in_kobo' => $this->calculatePaystackCharge($trxn->amount) * 100,
                'total_in_naira' => $this->calculatePaystackCharge($trxn->amount),
            ],
            'pkey' => config('vars.paystack.pub_key'),
            'meta' => [
                'reference' => $trxn->reference,
                'email' => $user->email, //@phpstan-ignore-line
                'type' => ServiceType::FLIGHT->value,
                'amount' => $trxn->amount,
                'offer_id' => $offer?->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function initiateFlightPaymentWithFlutterwave(array $data): array
    {
        $trxn = $this->validateDataAndReturnTransaction($data, Gateway::FLUTTERWAVE->value);
        $gatewayCharge = $this->calculateFlutterwaveCharge($trxn->amount);
        $user = $trxn->user;
        $offer = $trxn->offer;

        return [
            'reference' => $trxn->reference,
            'amount' => $trxn->amount + $gatewayCharge,
            'break_down' => [
                'flight_amount' => $trxn->amount - $this->getFlightServiceCharge(),
                'service_charge' => $this->getFlightServiceCharge(),
                'gateway_charge' => $gatewayCharge,
                'total_in_kobo' => ($trxn->amount + $gatewayCharge) * 100,
                'total_in_naira' => $trxn->amount + $gatewayCharge,
            ],
            'pkey' => config('vars.flutterwave.pub_key'),
            'meta' => [
                'reference' => $trxn->reference,
                'email' => $user->email, //@phpstan-ignore-line
                'type' => ServiceType::FLIGHT->value,
                'amount' => $trxn->amount,
                'offer_id' => $offer?->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateDataAndReturnTransaction(array $data, string $gateway): Transaction
    {
        $user = request()->user();
        $offer = $this->flightOffers->whereId($data['offer_id'])->first();
        throw_if(blank($user) || blank($offer), CustomException::class, 'Offer unavailable');
        $flightPrice = $offer['price']['grandTotal'];
        $departureDate = Carbon::parse($offer['departure_date']);
        throw_if(today()->greaterThan($departureDate), CustomException::class, 'Flight date should a future date');
        throw_if(now()->diffInMinutes(Carbon::parse($offer->updated_at)) > 60, CustomException::class, 'Offer refreshed, start again');
        $reference = getRandomReference('FLP');
        $amount = $flightPrice + $this->getFlightServiceCharge();

        return $this->transaction->create([
            'user_id' => $user->id,
            'offer_id' => $offer->id,
            'reference' => $reference,
            'amount' => $amount,
            'description' => 'Payment for flight booking',
            'service_type' => ServiceType::FLIGHT->value,
            'transaction_type' => TransactionType::OUTFLOW->value,
            'category' => TransactionCat::FLIGHTBOOKING->value,
            'gateway' => $gateway,
            'status' => TransactionStatus::PENDING->value,
            'book_flight_id' => $data['booking_id'],
            'meta' => [
                'reference' => $reference,
                'email' => $user->email,
                'type' => ServiceType::FLIGHT->value,
                'amount' => $amount,
                'offer_id' => $offer->id,
            ],
        ]);
    }

    private function getFlightServiceCharge(): float
    {
        return $this->setting->first()?->flight_service_charge ?? 0;
    }



    // Fight Deals Payment Start from here

    public function makeDealPayment(): array
    {
        $messages = ['booking_id.required' => 'Booking is required'];
        $data = request()->validate([
            'channel' => 'bail|required|' . Rule::in(['paystack', 'flutterwave']),
            'deal_id' => 'bail|required|exists:deals,id',
            'booking_id' => 'bail|required|exists:book_deals,id',
        ], $messages);
        $channel = $data['channel'];

        return match ($channel) {
            'paystack' => $this->initiateDealPaymentWithPaystack($data),
            'flutterwave' => $this->initiateDealPaymentWithFlutterwave($data),
            default => throw new CustomException('Payment method not valid')
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function initiateDealPaymentWithPaystack(array $data): array
    {
        $trxn = $this->validateDealDataAndReturnTransaction($data, Gateway::PAYSTACK->value);
        $user = $trxn->user;
        $deal = $trxn->deal;

        return [
            'reference' => $trxn->reference,
            'amount' => $this->calculatePaystackCharge($trxn->amount) * 100,
            'break_down' => [
                'deal_amount' => $trxn->amount - $this->getDealServiceCharge(),
                'service_charge' => $this->getDealServiceCharge(),
                'gateway_charge' => $this->calculatePaystackCharge($trxn->amount) - $trxn->amount,
                'total_in_kobo' => $this->calculatePaystackCharge($trxn->amount) * 100,
                'total_in_naira' => $this->calculatePaystackCharge($trxn->amount),
            ],
            'pkey' => config('vars.paystack.pub_key'),
            'meta' => [
                'reference' => $trxn->reference,
                'email' => $user->email, //@phpstan-ignore-line
                'type' => ServiceType::DEAL->value,
                'amount' => $trxn->amount,
                'deal_id' => $deal?->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function initiateDealPaymentWithFlutterwave(array $data): array
    {
        $trxn = $this->validateDealDataAndReturnTransaction($data, Gateway::FLUTTERWAVE->value);
        $gatewayCharge = $this->calculateFlutterwaveCharge($trxn->amount);
        $user = $trxn->user;
        $deal = $trxn->deal;

        return [
            'reference' => $trxn->reference,
            'amount' => $trxn->amount + $gatewayCharge,
            'break_down' => [
                'deal_amount' => $trxn->amount - $this->getDealServiceCharge(),
                'service_charge' => $this->getDealServiceCharge(),
                'gateway_charge' => $gatewayCharge,
                'total_in_kobo' => ($trxn->amount + $gatewayCharge) * 100,
                'total_in_naira' => $trxn->amount + $gatewayCharge,
            ],
            'pkey' => config('vars.flutterwave.pub_key'),
            'meta' => [
                'reference' => $trxn->reference,
                'email' => $user->email, //@phpstan-ignore-line
                'type' => ServiceType::DEAL->value,
                'amount' => $trxn->amount,
                'deal_id' => $deal?->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateDealDataAndReturnTransaction(array $data, string $gateway): Transaction
    {
        $user = request()->user();
        $deal = FlightDeals::whereId($data['deal_id'])->first();
        throw_if(blank($user) || blank($deal), CustomException::class, 'Deal unavailable');
        $dealPrice = $deal['price'];
        throw_if(now()->diffInMinutes(Carbon::parse($deal->updated_at)) > 60, CustomException::class, 'Deal refreshed, start again');
        $reference = getRandomReference('DLP');
        $amount = $dealPrice + $this->getDealServiceCharge();

        return $this->transaction->create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'reference' => $reference,
            'amount' => $amount,
            'description' => 'Payment for deal booking',
            'service_type' => ServiceType::DEAL->value,
            'transaction_type' => TransactionType::OUTFLOW->value,
            'category' => TransactionCat::DEALBOOKING->value,
            'gateway' => $gateway,
            'status' => TransactionStatus::PENDING->value,
            'book_deal_id' => $data['booking_id'],
            'meta' => [
                'reference' => $reference,
                'email' => $user->email,
                'type' => ServiceType::DEAL->value,
                'amount' => $amount,
                'deal_id' => $deal->id,
            ],
        ]);
    }

    private function getDealServiceCharge(): float
    {
        return $this->setting->first()?->deal_service_charge ?? 0;
    }
}
