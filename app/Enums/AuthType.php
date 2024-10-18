<?php

namespace App\Enums;

enum AuthType: string
{
    case PASSWORD = 'password';
    case GOOGLE = 'google';
    case APPLE = 'apple';
}
