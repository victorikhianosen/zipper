<?php

namespace App\Enums\Status;

enum AdminStatus: string
{
    case ACTIVE = 'active';
    case RESTRICTED = 'restricted';
}
