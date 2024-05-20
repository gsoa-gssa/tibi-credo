<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SheetsSettings extends Settings
{

    public array $sources = [
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
    ];

    public static function group(): string
    {
        return 'sheets';
    }
}
