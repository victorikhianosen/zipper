<?php

namespace App\Services;

use App\Enums\Status\FlightStatus;
use App\Models\BookFlight;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

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
    public function myFlightBookingStats(): array
    {
        $user = request()->user();
        $flights = $this->bookFlight->where('user_id', $user->id)->with(['transactions']); //@phpstan-ignore-line

        return [
            'total' => $flights->count(),
            'ongoing' => $flights->whereStatus(FlightStatus::PENDING->value)->count(),
            'upcoming' => $flights->whereStatus(FlightStatus::PENDING->value)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function myFlightBookings(): array
    {
        $filter = request()->query('status');
        $search = request()->query('search');
        $user = request()->user();
        $flights = $this->bookFlight->where('user_id', $user->id)->with(['transactions']); //@phpstan-ignore-line
        if (filled($filter)) {
            match ($filter) {
                'pending' => $flights->whereStatus($filter) ,
                'failed' => $flights->whereStatus($filter)  ,
                default => $flights->whereTrackingStatus($filter)
            };
        }

        if (filled($search)) {
            $this->addSearchQuery($flights, $search); //@phpstan-ignore-line
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

    /**
     * @return array<string, mixed>
     */
    public function getUsers(): array
    {
        $query = User::query();
        $this->applyUserFilter($query);

        return $this->paginate(query: $query);
    }

    private function addSearchQuery(BookFlight|Builder $flights, string $search): mixed
    {
        return $flights
            ->orWhere('search_meta->trip_type', 'like', "%$search%")
            ->orWhere('search_meta->flight_class', 'like', "%$search%")
            ->orWhere('carrier', 'like', "%$search%");
    }

    protected function applyUserFilter(Builder &$query): Builder
    {
        $filter = Arr::get(request()->all(), 'filter', []);
        foreach ($filter as $column => $value) {
            $query = $query->where($column, $value);
        }

        return $query;
    }
}
