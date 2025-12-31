<?php

namespace App\Filament\RelationManagers;

use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

abstract class BaseActivitylogRelationManager extends ActivitylogRelationManager
{
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        $form = parent::form($form);
        return $form;
    }

    public function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();
        if (!$query) {
            $query = \Spatie\Activitylog\Models\Activity::query();
            $owner = $this->getOwnerRecord();
            $query->where('subject_type', $owner::class)
                  ->where('subject_id', $owner->id);
        }

        $signatureCollectionId = auth()->user()->signature_collection_id;
        if ($signatureCollectionId) {
            $query->where(function ($q) use ($signatureCollectionId) {
                $q->where('properties->signature_collection_id', $signatureCollectionId)
                ->orWhereNull('properties->signature_collection_id');
            });
        }

        return $query;
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        $table = parent::table($table);
        $columns = $table->getColumns();
        // find the column with name log_name and hide it by default
        $logNameColumn = collect($columns)->first(fn($column) => $column->getName() === 'log_name');
        $logNameColumn?->toggleable(isToggledHiddenByDefault: true);
        // append a summary column to $columns
        $columns[] = \Filament\Tables\Columns\TextColumn::make('summary')
            ->label('Zusammenfassung')
            ->html()
            ->getStateUsing(fn ($record) => $this->summarize($record));
        $table->columns($columns);
        // disable view button
        $table->actions([]);
        // Add a button to add a new object (activity log entry)
        $table->headerActions([
            \Filament\Tables\Actions\Action::make('addActivityLog')
                ->label('Add Comment')
                ->form([
                    \Filament\Forms\Components\TextInput::make('message')
                        ->label('Message')
                        ->required(),
                ])
                ->action(function (array $data, $livewire) {
                    $this->addActivityLogEntry($data['message']);
                }),
        ]);
        return $table;
    }

    protected function addActivityLogEntry(string $message): void
    {
        $signatureCollectionId = auth()->user()->signature_collection_id ?? null;
        activity()
            ->on($this->getOwnerRecord())
            ->event('comment')
            ->withProperties(['signature_collection_id' => $signatureCollectionId])
            ->log($message);
    }

    protected function summarize($record): string
    {
        if ($record->event == 'comment') {
            return $record->description;
        } else {
            $changes = $record->changes->toArray();
            if (array_key_exists('attributes', $changes)) {
                if (array_key_exists('old', $changes)) {
                    $merged = [['Spalte', 'Neu', 'Vorher']];
                    foreach ($changes['attributes'] as $key => $value) {
                        if ($key != "updated_at" && array_key_exists($key, $changes['old']) && $changes['old'][$key] !== $value) {
                            $merged[] = [$key, json_encode($value), json_encode($changes['old'][$key])];
                        }
                    }
                    $html = '<table>';
                    foreach ($merged as [$key, $value, $oldValue]) {
                        $html .= '<tr>';
                        $html .= '<td>' . $key . '</td>';
                        $html .= '<td>' . $oldValue . '</td>';
                        $html .= '<td>' . $value . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                    return $html;
                } else {
                    $unwanted_keys = ['created_at', 'updated_at', 'deleted_at'];
                    $changes['attributes'] = array_filter($changes['attributes'], fn($key) => !in_array($key, $unwanted_keys), ARRAY_FILTER_USE_KEY);
                    $html = '<table>';
                    foreach ($changes['attributes'] as $key => $value) {
                        $html .= '<tr>';
                        $html .= '<td>' . $key . '</td>';
                        $html .= '<td>' . $value . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table>';
                    return $html;
                }
            }
            return '';
        }
    }
}
