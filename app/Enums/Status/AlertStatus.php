<?php

namespace App\Enums\Status;

enum AlertStatus: string
{
    case PENDING = 'pending';
    case ONGOING = 'ongoing';
    case FAILED = 'failed';
    case SUCCESSFUL = 'successful';
}
