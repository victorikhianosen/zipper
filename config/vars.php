<?php

return [
    'amadeus' => [
        'url' => env('AMADEUS_API_URL'),
        'api_key' => env('AMADEUS_API_KEY'),
        'secret_key' => env('AMADEUS_SECRET_KEY'),
        'flight_offers' => env('AMADEUS_API_URL').'/v2/shopping/flight-offers',
        'flight_offers_price' => env('AMADEUS_API_URL').'/v1/shopping/flight-offers/pricing',
    ],
];
