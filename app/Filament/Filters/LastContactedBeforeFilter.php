<?php

namespace App\Filament\Filters;

use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class LastContactedBeforeFilter
{
    public static function make()
    {
        return Tables\Filters\SelectFilter::make('last_contacted_before')
            ->label(__('commune.filters.last_contacted_on_before'))
            ->options([
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                '2_days' => '2 days ago',
                '3_days' => '3 days ago',
                '4_days' => '4 days ago',
                '5_days' => '5 days ago',
                '1_week' => '1 week ago',
                '2_weeks' => '2 weeks ago',
                '1_month' => '1 month ago',
                'more_than_1_month' => 'More than 1 month ago',
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (!$data['value']) {
                    return $query;
                }
                $date = match($data['value']) {
                    'today' => now()->startOfDay(),
                    'yesterday' => now()->subDay()->startOfDay(),
                    '2_days' => now()->subDays(2)->startOfDay(),
                    '3_days' => now()->subDays(3)->startOfDay(),
                    '4_days' => now()->subDays(4)->startOfDay(),
                    '5_days' => now()->subDays(5)->startOfDay(),
                    '1_week' => now()->subWeek()->startOfDay(),
                    '2_weeks' => now()->subWeeks(2)->startOfDay(),
                    '1_month' => now()->subMonth()->startOfDay(),
                    'more_than_1_month' => now()->subMonth()->startOfDay(),
                    default => null,
                };
                if (!$date) {
                    return $query;
                }
                return $query->where(function ($q) use ($date) {
                    $q->where('last_contacted_on', '<', $date)
                      ->orWhereNull('last_contacted_on');
                });
            });
    }
}
