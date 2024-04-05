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
    protected $description = 'Delete expired offers';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $recordsToDelete = FlightOffers::where('created_at', '<=', Carbon::now()->subHours(2))->get();

        foreach ($recordsToDelete as $record) {
            $record->delete();
        }
    }
}
