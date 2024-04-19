<?php

namespace App\Services;

use App\Models\BookFlight;
use App\Models\Transaction;

class UserService extends BaseService
{
    public function __construct(
        protected BookFlight $bookFlight,
        protected Transaction $transaction
    ) {

    }

    /**
     * @return array<string, mixed>
     */
    public function myFlightBookings(): array
    {
        $filter = request()->query('filter');
        $user = request()->user();
        $flights = $this->bookFlight->where('user_id', $user->id)->with(['transactions']); //@phpstan-ignore-line
        if (filled($filter)) {
            $flights->whereTrackingStatus($filter);
        }

        return $this->paginate(query: $flights);

    }

    /**
     * @return array<string, mixed>
     */
    public function myTransactions(): array
    {
        $filter = request()->query('filter');
        $user = request()->user();
        $flights = $this->transaction->where('user_id', $user->id)->whereNotNull('book_flight_id'); //@phpstan-ignore-line
        if (filled($filter)) {
            $flights->whereTrackingStatus($filter);
        }

        return $this->paginate(query: $flights);

    }

    /**
     * @return array<string, mixed>
     */
    public function singleTransaction(Transaction $transaction): array
    {
        $filter = request()->query('filter');
        $user = request()->user();
        $flights = $this->transaction->where('user_id', $user->id)->whereNotNull('book_flight_id'); //@phpstan-ignore-line
        if (filled($filter)) {
            $flights->whereTrackingStatus($filter);
        }

        return $this->paginate(query: $flights);

    }
}
