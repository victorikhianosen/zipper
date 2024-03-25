<?php

namespace App\Services;

use App\Comms\Amadeus;

class FlightService extends BaseService
{
    public function __construct(
        protected Amadeus $amadeus
    ) {
    }

    // public function searchOffers()
    // {

    // }
}
