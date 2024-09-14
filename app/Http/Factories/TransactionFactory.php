<?php

namespace App\Http\Factories;

use App\Enums\Gateway;
use App\Enums\ServiceType;
use App\Enums\TransactionCat;
use App\Enums\TransactionStatus;
use App\Models\Transaction;

class TransactionFactory
{
    public static function createPointBonusTransaction(float $amount): Transaction
    {
        return Transaction::create([
            'user_id' => auth()->id(),
            'reference' => getRandomReference('PB'),
            'amount' => $amount,
            'description' => 'Point-credit',
            'service_type' => ServiceType::FLIGHT->value,
            'transaction_type' => 'cr',
            'category' => TransactionCat::COMMISSION->value,
            'gateway' => Gateway::ZIPPA->value,
            'status' => TransactionStatus::SUCCESS->value,
        ]);
    }
}
