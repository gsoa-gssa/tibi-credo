<?php

namespace App\Filament\Pages;

use App\Models\Commune;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class CaptureBatchWorkflow extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    public function getTitle(): string
    {
        return __('pages.captureBatchWorkflow.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('pages.captureBatchWorkflow.navigationLabel');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pages.captureBatchWorkflow.navigationGroup');
    }

    public function mount(): void
    {
        $this->selectCommuneForm->fill();
        $this->certificationTypeForm->fill();
        $this->individualCertificationForm->fill();
        $this->collectiveCertificationForm->fill();
    }

    public function getForms(): array
    {
        return [
            'selectCommuneForm',
            'certificationTypeForm',
            'individualCertificationForm',
            'collectiveCertificationForm',
        ];
    }

    public $data;
    public function selectCommuneForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('commune')
                    ->label(__('pages.captureBatchWorkflow.selectCommuneForm.commune'))
                    ->options(Commune::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive(),
            ])
            ->statePath('data');
    }

    public function certificationTypeForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Radio::make('certification_type')
                    ->label(__('pages.captureBatchWorkflow.certificationTypeForm.certificationType'))
                    ->options([
                        'individual' => __('pages.captureBatchWorkflow.certificationTypeForm.individual'),
                        'collective' => __('pages.captureBatchWorkflow.certificationTypeForm.collective'),
                    ])
                    ->required()
                    ->reactive(),
            ])
            ->statePath('data');
    }

    public function individualCertificationForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\CheckboxList::make('requirements')
                    ->label(__('pages.captureBatchWorkflow.individualCertificationForm.requirements'))
                    ->options([
                        'control_field' => __('pages.captureBatchWorkflow.individualCertificationForm.control_field'),
                        'number_of_valid_signatures' => __('pages.captureBatchWorkflow.individualCertificationForm.number_of_valid_signatures'),
                        'controling_authority_information' => __('pages.captureBatchWorkflow.individualCertificationForm.controling_authority_information'),
                        'seal' => __('pages.captureBatchWorkflow.individualCertificationForm.seal'),
                    ])
                    ->descriptions([
                        'control_field' => __('pages.captureBatchWorkflow.individualCertificationForm.control_field_description'),
                        'controling_authority_information' => __('pages.captureBatchWorkflow.individualCertificationForm.controling_authority_information_description'),
                        'seal' => __('pages.captureBatchWorkflow.individualCertificationForm.seal_description'),
                    ])
                    ->columns(2)
            ])
            ->statePath('data');
    }

    public function collectiveCertificationForm(Form $form): Form
    {
        return $form
            ->schema([
                
            ])
            ->statePath('data');
    }

    protected static string $view = 'filament.pages.capture-batch-workflow';
}
