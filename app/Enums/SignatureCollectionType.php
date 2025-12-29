<?php

namespace App\Enums;

enum SignatureCollectionType: string
{
    case FEDERAL_INITIATIVE = 'federal_initiative';
    case FEDERAL_REFERENDUM = 'federal_referendum';
    
    public function label(): string
    {
        return __("signature_collection_types.{$this->value}");
    }
    
    public function description(): string
    {
        return __("signature_collection_types.{$this->value}_description");
    }
    
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
