<?php

namespace App\Console\Commands;

use App\Models\BookFlight;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FlightTrackingStatusUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:tracking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update flight tracking status';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $flights = BookFlight::whereStatus('completed')
            ->where('tracking_status', '!=', 'completed')->get();
        foreach ($flights as $flight) {
            $searchMeta = $flight->search_meta;
            $tripType = $searchMeta['trip_type']; //@phpstan-ignore-line
            $flightOffers = $flight->flight_offers;
            $itineraries = $flightOffers[0]['itineraries']; //@phpstan-ignore-line
            $itinerariesCount = count($itineraries);
            // when it's one-way
            if ($tripType == 'one' && $itinerariesCount == 1) {
                $segments = $itineraries[0]['segments'];
                $this->checkSegmentAndUpdate($segments, $flight);
            }

            // when it's round-trip
            if ($tripType == 'round' && $itinerariesCount == 2) {
                $lastItinerary = $itineraries[$itinerariesCount - 1];
                $segments = $lastItinerary['segments'];
                $this->checkSegmentAndUpdate($segments, $flight);
            }
        }
    }

    protected function updateTrackingStatus(string $date, BookFlight $flight): void
    {
        $arrivalDate = Carbon::parse($date);
        $today = today();
        if ($arrivalDate->greaterThan($today)) {
            $flight->update(['tracking_status' => 'upcoming']);
        }
        if ($arrivalDate->lessThan($today)) {
            $flight->update(['tracking_status' => 'completed']);
        }

        if ($arrivalDate->equalTo($today)) {
            $flight->update(['tracking_status' => 'active']);
        }
    }

    /**
     * @param  array<string, mixed>  $segments
     */
    protected function checkSegmentAndUpdate(array $segments, BookFlight $flight): void
    {
        //there is no stop-over
        if (count($segments) == 1) {
            $arrivalDate = $segments[0]['arrival']['at']; //@phpstan-ignore-line
            $this->updateTrackingStatus(date: $arrivalDate, flight: $flight);
        }

        // if there is stop-over
        if (count($segments) > 1) {
            $lastSegment = $segments[count($segments) - 1]; //@phpstan-ignore-line
            $arrivalDate = $lastSegment['arrival']['at'];
            $this->updateTrackingStatus(date: $arrivalDate, flight: $flight);
            logger($arrivalDate);
        }
    }
}
