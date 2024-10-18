<?php

namespace App\Enums;

enum TransactionType: string
{
    case INFLOW = 'cr';
    case OUTFLOW = 'dr';
}
