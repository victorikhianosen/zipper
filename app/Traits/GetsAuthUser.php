<?php

namespace App\Traits;

use App\Models\User;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

trait GetsAuthUser
{
    protected function authUser(): User
    {
        $user = auth()->user();
        if (! $user) {
            throw new BadRequestException('Auth user not found', 404);
        }

        return $user;
    }
}
