<?php

namespace App\Filament\Resources\CommuneResource\Filters;

use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;

class SignaturesInOpenBatchesFilter
{
    public static function make()
    {
        return Tables\Filters\Filter::make('signatures_in_open_batches')
            ->form([
                Forms\Components\TextInput::make('min_signatures')
                    ->label(__('commune.filters.signatures_in_open_batches.min_signatures'))
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('days_ago')
                    ->label(__('commune.filters.signatures_in_open_batches.days_ago'))
                    ->numeric()
                    ->default(7),
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (
                    empty($data)
                    || !isset($data['min_signatures'])
                    || !isset($data['days_ago'])
                    || !is_numeric($data['min_signatures'])
                    || !is_numeric($data['days_ago'])
                    || $data['min_signatures'] <= 0
                ) {
                    return $query->whereRaw('1 = 0');
                }
                $minSignatures = $data['min_signatures'] ?? 1;
                $daysAgo = $data['days_ago'] ?? 7;
                $date = now()->subDays($daysAgo);
                return $query->whereHas('batches', function ($q) use ($date, $minSignatures) {
                    $q->where('open', true)
                      ->where('expected_return_date', '<=', $date)
                      ->havingRaw('SUM(sheets_count) > ?', [$minSignatures])
                      ->groupBy('commune_id');
                });
            });
    }
}
