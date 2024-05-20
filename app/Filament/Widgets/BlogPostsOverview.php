<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\Sheet;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class BlogPostsOverview extends BaseWidget
{
    use HasWidgetShield;

    public $openScans;
    public $registeredSignatures;
    public $sentoutBatches;

    public function __construct()
    {
        $this->openScans = count(glob(storage_path('app/public/sheet-scans/unassigned/*.pdf')));
        $this->registeredSignatures = Sheet::all()->sum('signatureCount');
        $this->sentoutBatches = Batch::where('status', 'sentout')->get()->count();
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('Open Sheets'), $this->openScans),
            Stat::make(__('Collected Signatures'), $this->registeredSignatures),
            Stat::make(__('Batches sent to communes'), $this->sentoutBatches),
        ];
    }
}
