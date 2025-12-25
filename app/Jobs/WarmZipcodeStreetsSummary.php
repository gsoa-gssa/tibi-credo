<?php

namespace App\Jobs;

use App\Models\Zipcode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WarmZipcodeStreetsSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array<int>
     */
    public array $zipcodeIds;

    /**
     * Create a new job instance.
     *
     * @param array<int> $zipcodeIds
     */
    public function __construct(array $zipcodeIds)
    {
        $this->zipcodeIds = $zipcodeIds;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->zipcodeIds as $id) {
            $zipcode = Zipcode::find($id);
            if (!$zipcode) {
                continue;
            }
            try {
                // Populate cache by calling the summary method
                $zipcode->getStreetsWithNumbersSummary();
            } catch (\Throwable $e) {
                // Avoid failing the whole batch; log and continue
                \Log::warning('WarmZipcodeStreetsSummary failed for zipcode ID ' . $id . ': ' . $e->getMessage());
            }
        }
    }
}
