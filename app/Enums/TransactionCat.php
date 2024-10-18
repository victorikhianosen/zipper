<?php

namespace App\Enums;

enum TransactionCat: string
{
    // regardless of this category, appropriate type (CR, DR) should be applied
    case CHARGE = 'service-charge';
    case DEPOSIT = 'deposit';
    case COMMISSION = 'commission';
    case WITHDRAWAL = 'withdrawal';
    case REFUND = 'refund';
    case REVERSAL = 'reversal';
    case FLIGHTBOOKING = 'flight_booking';
}
