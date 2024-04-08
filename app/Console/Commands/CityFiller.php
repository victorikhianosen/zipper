<?php

namespace App\Console\Commands;

use App\Comms\Connection;
use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CityFiller extends Command
{
    public function __construct(
        protected Connection $server
    ) {
        parent::__construct();
    }

    protected $signature = 'fill:city';

    protected $description = 'Fill City table with country and city code';

    public function handle(): void
    {
        $count = City::count();
        if ($count == 0) {
            $endPoint = 'https://airlabs.co/api/v9/cities';
            try {

                $response = $this->server->sendGet(url: $endPoint, query: ['api_key' => 'f54d91c6-9fbb-454b-b262-b716b5fd5896']);
                DB::beginTransaction();
                if (filled($response)) {
                    foreach ($response['response'] as $resp) {
                        City::query()->create([
                            'city_name' => $resp['name'],
                            'city_code' => $resp['city_code'],
                            'measurement' => ['lat' => $resp['lat'], 'long' => $resp['lng']],
                            'country_code' => $resp['country_code'],
                        ]);
                    }
                }
                DB::commit();
            } catch (\Throwable $th) {
                info($th);
                exit;
            }
        }
        exit;

    }
}
