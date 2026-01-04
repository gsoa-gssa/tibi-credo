<?php

namespace App\Filament\Resources\CommuneResource\Filters;

use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use App\Models\Commune;

class HasCommentsAfterFilter
{
    public static function make()
    {
        return Tables\Filters\Filter::make('has_comments_after')
            ->label(__('commune.filters.has_comments_after'))
            ->form([
                Forms\Components\DatePicker::make('has_comments_after')
                    ->label(__('commune.filters.has_comments_after')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                $date = $data['has_comments_after'] ?? null;
                if (empty($date)) {
                    return $query;
                }

                $activityTable = (new Activity())->getTable();

                return $query->whereExists(function ($q) use ($activityTable, $date) {
                    $q->selectRaw('1')
                      ->from($activityTable)
                      ->whereColumn('subject_id', (new Commune())->getTable() . '.id')
                      ->where('subject_type', Commune::class)
                      ->where('event', 'comment')
                      ->whereDate('created_at', '>', $date);
                });
            });
    }
}
