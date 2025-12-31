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
    public function getLocalized(string $baseField): string
    {
        $locale = app()->getLocale();
        $field = "{$baseField}_{$locale}";

        if (isset($this->$field)) {
            return $this->$field;
        }

        // If any localized field exists, use it, for languages de, fr, it
        foreach (['de', 'fr', 'it'] as $fallbackLocale) {
            if ($locale !== $fallbackLocale) {
                $fallbackField = "{$baseField}_{$fallbackLocale}";
                if (isset($this->$fallbackField)) {
                    return $this->$fallbackField;
                }
            }
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
