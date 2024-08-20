<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AlertDispatcher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Alert $alert)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AlertService $service): void
    {
        $service->dispatch($this->alert);
    }
}
