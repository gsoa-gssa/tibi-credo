<?php

namespace App\Filament\Filters;

use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CheckedOnFilter
{
    public static function make()
    {
        return Tables\Filters\SelectFilter::make('checked_on')
            ->label(__('commune.filters.checked_on'))
            ->options(function () {
                $dates = \App\Models\Commune::query()
                    ->whereNotNull('checked_on')
                    ->select('checked_on')
                    ->distinct()
                    ->orderBy('checked_on', 'desc')
                    ->get()
                    ->pluck('checked_on')
                    ->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->toDateString())
                    ->unique()
                    ->mapWithKeys(fn($d) => [$d => $d])
                    ->all();
                return ['__null__' => __('commune.filters.checked_on.never')] + $dates;
            })
            ->query(function (Builder $query, array $data): Builder {
                $val = $data['value'] ?? null;
                if (!$val) {
                    return $query;
                }
                if ($val === '__null__') {
                    return $query->whereNull('checked_on');
                }
                return $query->whereDate('checked_on', $val);
            });
    }
}
