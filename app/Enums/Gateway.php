<?php

namespace App\Enums;

enum Gateway: string
{
    case PAYSTACK = 'paystack';
    case FLUTTERWAVE = 'flutterwave';
    case ZIPPA = 'zippa';
}
