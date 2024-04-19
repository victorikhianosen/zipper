<?php

namespace App\Services;

use App\Models\BookFlight;
use App\Models\Transaction;

class DashboardService extends BaseService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboardIndex(): array
    {
        $data = [
            'total_bookings' => BookFlight::count(),
            'total_transactions' => Transaction::count(),
            'total_flight_bookings' => BookFlight::count(),
            'total_hotel_bookings' => 0,
        ];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFlightBookings(): array
    {
        $query = BookFlight::withRelationAndColumns('user', ['email']);

        return $this->paginate($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransactions(): array
    {
        $query = Transaction::withRelationAndColumns('user', ['email']);

        return $this->paginate($query);
    }
}
