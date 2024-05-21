<?php

namespace App\Console\Commands;

use App\Models\FlightOffers;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ManageOfferCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune:offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired offers or deals';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // offers
        $recordsToDelete = FlightOffers::whereNull('saved_by')->whereNull('expires_at')->where('created_at', '<=', Carbon::now()->subHours(1))->get();

        foreach ($recordsToDelete as $record) {
            $record->delete();
        }

        // deals, delete deals that have expired

        // Get the current date
        $today = now()->toDateString();
        // Delete records that have expired
        FlightOffers::whereDate('expires_at', $today)->delete();
    }
}
