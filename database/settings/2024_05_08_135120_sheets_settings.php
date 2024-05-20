<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sheets.sources', [
            [
                'value' => '001',
                'label' => 'Source 1'
            ],
            [
                'value' => '002',
                'label' => 'Source 2'
            ],
            [
                'value' => '003',
                'label' => 'Source 3'
            ]
        ]);
    }
};
