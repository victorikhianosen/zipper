<?php

namespace App\Enums\Status;

use App\Traits\EnumTrait;

enum UserStatus: string
{
    use EnumTrait;

    case ACTIVE = 'active';
    case RESTRICTED = 'restricted';
}
