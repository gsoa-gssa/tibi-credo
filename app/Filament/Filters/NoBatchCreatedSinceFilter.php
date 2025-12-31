<?php

namespace App\Filament\Filters;

use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class NoBatchCreatedSinceFilter
{
    public static function make()
    {
        return Tables\Filters\SelectFilter::make('no_batch_created_since')
            ->label(__('commune.filters.no_batch_created_since'))
            ->options([
                'today' => __('commune.filters.no_batch_created_since.today'),
                'since_yesterday' => __('commune.filters.no_batch_created_since.since_yesterday'),
                'since_1_week' => __('commune.filters.no_batch_created_since.since_1_week'),
                'since_1_month' => __('commune.filters.no_batch_created_since.since_1_month'),
                'since_3_months' => __('commune.filters.no_batch_created_since.since_3_months'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (!$data['value']) {
                    return $query;
                }
                $date = match($data['value']) {
                    'today' => now()->startOfDay(),
                    'since_yesterday' => now()->subDay()->startOfDay(),
                    'since_1_week' => now()->subWeek()->startOfDay(),
                    'since_1_month' => now()->subMonth()->startOfDay(),
                    'since_3_months' => now()->subMonths(3)->startOfDay(),
                    default => null,
                };
                if (!$date) {
                    return $query;
                }
                return $query->whereDoesntHave('batches', function ($q) use ($date) {
                    $q->whereNull('deleted_at')->where('created_at', '>=', $date);
                });
            });
    }
}
