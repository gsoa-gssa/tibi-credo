<?php

namespace App\Traits;

trait HasLocalizedFields
{
    /**
     * Get a localized field value, falling back to a default locale if not set.
     *
     * @param string $baseField The base field name (e.g. 'official_name')
     * @param string|null $fallbackLocale The fallback locale (default: 'de')
     * @return string
     */
    public function getLocalized(string $baseField, ?string $fallbackLocale = 'de'): string
    {
        $locale = app()->getLocale();
        $field = "{$baseField}_{$locale}";
        $fallbackField = "{$baseField}_{$fallbackLocale}";
        return $this->$field ?? $this->$fallbackField ?? '';
    }
}
