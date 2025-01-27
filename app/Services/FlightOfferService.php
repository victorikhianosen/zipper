<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Models\FlightOffers;

class FlightOfferService extends BaseService
{
    public function addToWishList(FlightOffers $offer): void
    {
        if (blank($offer)) {
            throw new CustomException('Offer not found');
        } else {
            $offer->update(['saved_by' => auth()->id()]);
        }
    }

    public function deleteFromWishList(FlightOffers $offer): void
    {
        if (blank($offer)) {
            throw new CustomException('Offer not found');
        } else {
            $offer->update(['saved_by' => null]);
        }
    }

    /**
     * @return array<mixed,mixed>
     */
    public function getWishLists(): array
    {
        $lists = FlightOffers::whereSavedBy(auth()->id());

        return $this->paginate(query: $lists);
    }


    public function getAllDeals(): array
    {
        return [
            'data' => FlightOffers::all(),
            'meta' => [
                'total' => FlightOffers::count(),
                'timestamp' => now()->toDateTimeString(),
            ],
        ];
    }
}
