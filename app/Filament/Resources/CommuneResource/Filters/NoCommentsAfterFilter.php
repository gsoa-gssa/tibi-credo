<?php

namespace App\Filament\Resources\CommuneResource\Filters;

use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use App\Models\Commune;

class NoCommentsAfterFilter
{
    public static function make()
    {
        return Tables\Filters\Filter::make('no_comments_after')
            ->label(__('commune.filters.no_comments_after'))
            ->form([
                Forms\Components\DatePicker::make('no_comments_after')
                    ->label(__('commune.filters.no_comments_after')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                $date = $data['no_comments_after'] ?? null;
                if (empty($date)) {
                    return $query;
                }

                $activityTable = (new Activity())->getTable();

                // Include communes that have NO comments, or whose last comment is on/before the given date.
                // We do this by excluding any commune that has a comment AFTER the chosen date.
                return $query->whereNotExists(function ($q) use ($activityTable, $date) {
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
