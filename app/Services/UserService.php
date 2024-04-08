<?php

namespace App\Services;

use App\Models\BookFlight;

class UserService extends BaseService
{
    public function __construct(
        protected BookFlight $bookFlight
    ) {

    }

    /**
     * @return array<string, mixed>
     */
    public function getFlightBookings(): array
    {
        $filter = request()->query('filter');
        $user = request()->user();
        $flights = $this->bookFlight->where('user_id', $user->id); //@phpstan-ignore-line
        if (filled($filter)) {
            $flights->whereTrackingStatus($filter);
        }

        return $this->paginate(query: $flights);

    }
}
