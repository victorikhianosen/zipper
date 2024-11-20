<?php

namespace App\Services;

use App\Models\BookFlight;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

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
        $query = BookFlight::withRelationAndColumns('user', ['email', 'first_name', 'last_name', 'phone']);
        $this->applyFlightBookingFilter($query);

        return $this->paginate($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransactions(): array
    {
        $query = Transaction::withRelationAndColumns('user', ['email', 'first_name', 'last_name', 'phone']);

        return $this->paginate($query);
    }

    protected function applyFlightBookingFilter(Builder &$query): Builder
    {
        $filter = Arr::get(request()->all(), 'filter', []);
        foreach ($filter as $column => $value) {
            $query = $query->whereJsonContains("search_meta->$column", $value);
        }

        return $query;
    }
}
