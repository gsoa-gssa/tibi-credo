<?php

namespace App\Filament\Resources\CommuneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class OverviewRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';
    protected static string $realRelationship = 'communeOverview';
    private ?Collection $data = null;
    private ?Collection $headers = null;

    private function ensureDataInitialized(): void
    {
        if ($this->data === null) {
            $this->data = Collection::make($this->ownerRecord->overview() ?? []);
        }
        if ($this->headers === null) {
            $this->headers = $this->data->first() ? Collection::make(array_keys((array)$this->data->first())) : Collection::make();
        }
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('communeOverview.name');
    }

    public function mount(): void
    {
        $this->ensureDataInitialized();
    }

    private function getTableSchema(): array
    {
        $this->ensureDataInitialized();

        $columns = [];
        foreach ($this->headers as $header) {
            $column = Tables\Columns\TextColumn::make($header)
                ->label(__(static::$realRelationship . '.' . $header));
            
            if ($header === 'datetime') {
                $column->dateTime('d.m.Y H:i');
            } else {
                $column->wrap();
            }
            
            $columns[] = $column;
        }
        return $columns;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns($this->getTableSchema())
            ->headerActions([
                \Filament\Tables\Actions\Action::make('addActivityLog')
                    ->label(__('activityLog.addComment.button'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('message')
                            ->label(__('activityLog.addComment.message'))
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $this->getOwnerRecord()->addComment($data['message']);
                    }),
        ]);
    }

    public function getTableRecords(): Collection
    {
        $this->ensureDataInitialized();

        // Create anonymous Model instances for test data
        $modelClass = new class extends Model {
            protected $guarded = [];
            public $timestamps = false;
            public $incrementing = false;
            protected $keyType = 'string';
        };
        
        // Convert overview data to Model instances
        $data = [];
        foreach ($this->data as $index => $row) {
            $data[] = $modelClass->newInstance(array_merge(
                $row,
                ['id' => (string)($index + 1)]
            ))->setAttribute('id', (string)($index + 1));
        }
        
        return Collection::make($data);
    }

    public function paginateTableQuery(Builder $query): LengthAwarePaginator
    {
        $this->ensureDataInitialized();

        $data = $this->getTableRecords();
        $perPage = $this->getTableRecordsPerPage();
        $page = request()->get('page', 1);
        
        return new LengthAwarePaginator(
            $data->forPage($page, $perPage),
            $data->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }
}
