<?php

namespace App\Http\Factories;

use App\Models\Referral;
use App\Models\User;

class UserFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): User
    {
        if (isset($data['ref_code'])) {
            $referee = User::where('ref_code', $data['ref_code'])->first();
        }
        $data['ref_code'] = User::generateRefCode();
        $user = User::create($data);
        if (isset($referee)) {
            Referral::create([
                'referee_id' => $referee['id'],
                'referred_id' => $user['id'],
                'amount' => 1000,
            ]);
        }

        return $user;
    }
}
