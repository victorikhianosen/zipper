<?php

namespace App\Enums\Status;

enum FlightStatus: string
{
    case UPCOMING = 'upcoming';
    case COMPLETED = 'completed';
    case ACTIVE = 'active';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case PENDING = 'pending';
    case TRXFAILED = 'trxfailed';
    case TRXPENDING = 'trxpending';
}
