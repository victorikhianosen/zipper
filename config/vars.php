<?php

return [
    'amadeus' => [
        'url' => env('AMADEUS_API_URL'),
        'api_key' => env('AMADEUS_API_KEY'),
        'secret_key' => env('AMADEUS_SECRET_KEY'),
        'flight_offers' => env('AMADEUS_API_URL').'/v2/shopping/flight-offers',
        'flight_offers_price' => env('AMADEUS_API_URL').'/v1/shopping/flight-offers/pricing',
        'flight_book' => env('AMADEUS_API_URL').'/v1/booking/flight-orders',
        'flight_deals' => env('AMADEUS_API_URL').'/v1/shopping/flight-dates',
    ],

    'paystack' => [
        'pub_key' => env('PAYSTACK_PUBLIC_KEY'),
        'sec_key' => env('PAYSTACK_SECRET_KEY'),
    ],

    'flutterwave' => [
        'pub_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'sec_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'sec_hash' => env('FLUTTERWAVE_SECRET_HASH'),
    ],
];
