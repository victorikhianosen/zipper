<?php

namespace App\Services;

use App\Models\BookFlight;
use App\Models\User;

class AdminService extends BaseService
{
    public function __construct(
        protected BookFlight $flight
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserFlightBookings(User $user): array
    {
        $query = $this->flight->whereUserId($user->id);

        return $this->paginate($query);
    }

    public function makeReseller(User $user): void
    {
        $data = request()->validate(['callback_url' => 'bail|required|url']);
        $user->update(['is_reseller' => true, 'callback_url' => $data['callback_url']]);
    }
}
