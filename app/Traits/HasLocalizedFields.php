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

        // If any localized field exists, use it
        if (isset($this->$field) || isset($this->$fallbackField)) {
            return $this->$field ?? $this->$fallbackField ?? '';
        }

        // Otherwise, check for a JSON field with no suffix
        if (isset($this->$baseField) && is_string($this->$baseField)) {
            // Already a string, just return
            return $this->$baseField;
        }
        if (isset($this->$baseField) && is_array($this->$baseField)) {
            // If it's an array, try to get the value for the current locale or fallback
            return $this->$baseField[$locale] ?? $this->$baseField[$fallbackLocale] ?? '';
        }
        return '';
    }
}
