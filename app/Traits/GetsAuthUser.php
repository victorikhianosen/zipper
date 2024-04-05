<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\User;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

trait GetsAuthUser
{
    protected function authUser(): User
    {
        $user = auth()->user();
        if (! ($user instanceof User)) {
            throw new BadRequestException('Auth user not found', 404);
        }

        return $user;
    }

    protected function authAdmin(): Admin
    {
        $admin = request()->user();
        if (! ($admin instanceof Admin)) {
            throw new BadRequestException('Auth user not found', 404);
        }

        return $admin;
    }
}
