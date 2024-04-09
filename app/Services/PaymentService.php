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
     * @return array<string, mixed>
     */
    public function initiateFlightPaymentWithPaystack(): array
    {
        $data = request()->validate([
            'channel' => 'bail|required|'.Rule::in(['paystack', 'flutterwave']),
            'offer_id' => 'bail|required|exists:flight_offers,id',
        ]);
        $user = request()->user();
        $offer = $this->flightOffers->whereId($data['offer_id'])->first();
        $flightPrice = $offer['price']['grandTotal']; //@phpstan-ignore-line
        $departureDate = Carbon::parse($offer['departure_date']); //@phpstan-ignore-line
        throw_if(today()->greaterThan($departureDate), CustomException::class, 'Flight date should a future date');
        $charge = $this->setting->flight_service_charge;

        $trxn = $this->transaction->create([
            'user_id' => $user->id ?? null,
            'reference' => getRandomReference('FL'),
            'amount' => $flightPrice + $charge,
            'description' => 'Payment for flight booking',
            'service_type' => ServiceType::FLIGHT->value,
            'transaction_type' => TransactionType::OUTFLOW->value,
            'category' => TransactionCat::FLIGHTBOOKING->value,
            'gateway' => Gateway::PAYSTACK->value,
            'status' => TransactionStatus::PENDING->value,
        ]);

        return [
            'reference' => $trxn->reference,
            'amount' => $this->calculatePaystackCharge($trxn->amount) * 100,
            'meta' => [
                'reference' => $trxn->reference,
                'email' => $user->email ?? 'aa',
                'pkey' => config('vars.paystack.pub_key'),
                'type' => ServiceType::FLIGHT->value,
                'amount' => $trxn->amount,
            ],
        ];

    }
}
