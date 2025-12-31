<?php

namespace App\Filament\Pages;

use App\Models\Batch;
use App\Filament\Resources\BatchResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CreateBatchWorkflow extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.create-batch-workflow';

    public ?array $data = [];

    protected static string $model = Batch::class;

    public function getTitle(): string
    {
        return __('pages.createBatchWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.createBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.createBatchWorkflow.navigationGroup');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(BatchResource::getFormSchema())
            ->columns(2)
            ->statePath('data')
            ->model(Batch::class);
    }

    public function table(Table $table): Table
    {
        $batch_query = Batch::query()
                    ->with('commune')
                    ->whereDate('created_at', today())
                    ->whereHas('activities', function ($q) {
                        $q->where('causer_id', auth()->id())
                          ->where('event', 'created');
                    })
                    ->where('expected_delivery_date', null)
                    ->latest('created_at');
        return $table
            ->query(
                $batch_query->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('commune.name_with_canton_and_zipcode')
                    ->label(__('commune.name')),
                Tables\Columns\TextColumn::make('sheets_count')
                    ->label(__('batch.fields.sheets_count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('signature_count')
                    ->label(__('batch.fields.signature_count'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('weight_grams')
                    ->label(__('batch.fields.weight_grams'))
                    ->numeric(),
            ])
            ->bulkActions([
                \App\Filament\Actions\BulkActions\ExportBatchesBulkActionGroup::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            $batch = Batch::create($data);

            Notification::make()
                ->success()
                ->title(__('batch.name') . ' created')
                ->send();

            $this->form->fill();
            $this->dispatch('table-reloaded');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error creating batch')
                ->body($e->getMessage())
                ->send();
        }
    }
}
