<?php

namespace App\Services;

use App\Enums\Gateway;
use App\Enums\ServiceType;
use App\Enums\TransactionCat;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\CustomException;
use App\Models\FlightOffers;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PaymentService extends BaseService
{
    public function __construct(
        protected Transaction $transaction,
        protected FlightOffers $flightOffers,
        protected Setting $setting
    ) {

    }

    /**
     * @return array<string,mixed>
     */
    public function makePayment(): array
    {
        $data = request()->validate([
            'channel' => 'bail|required|'.Rule::in(['paystack', 'flutterwave']),
            'offer_id' => 'bail|required|exists:flight_offers,id',
            'booking_id' => 'bail|required|exists:book_flights,id',
        ]);
        $channel = $data['channel'];

        return match ($channel) {
            'paystack' => $this->initiateFlightPaymentWithPaystack($data) ,
            default => throw new CustomException('Payment method not valid')
        };

    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function initiateFlightPaymentWithPaystack(array $data): array
    {
        $user = request()->user();
        $offer = $this->flightOffers->whereId($data['offer_id'])->first();
        throw_if(blank($offer), CustomException::class, 'Offer unavailable');
        $flightPrice = $offer['price']['grandTotal'];
        $departureDate = Carbon::parse($offer['departure_date']);
        throw_if(today()->greaterThan($departureDate), CustomException::class, 'Flight date should a future date');
        throw_if(now()->diffInMinutes(Carbon::parse($offer->updated_at)) > 60, CustomException::class, 'Offer refreshed, please start again');
        $setting = $this->setting->first();
        $charge = $setting?->flight_service_charge;

        $trxn = $this->transaction->create([
            'user_id' => $user->id, //@phpstan-ignore-line
            'offer_id' => $offer->id,
            'reference' => getRandomReference('FLP'),
            'amount' => $flightPrice + $charge,
            'description' => 'Payment for flight booking',
            'service_type' => ServiceType::FLIGHT->value,
            'transaction_type' => TransactionType::OUTFLOW->value,
            'category' => TransactionCat::FLIGHTBOOKING->value,
            'gateway' => Gateway::PAYSTACK->value,
            'status' => TransactionStatus::PENDING->value,
            'book_flight_id' => $data['booking_id'],
        ]);

        return [
            'reference' => $trxn->reference,
            'amount' => $this->calculatePaystackCharge($trxn->amount) * 100,
            'break_down' => [
                'flight_amount' => $flightPrice,
                'service_charge' => $charge,
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
                'offer_id' => $offer->id,
            ],
        ];

    }
}
