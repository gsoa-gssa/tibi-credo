<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Canton extends Model
{
    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }

    public function getLocalizedName(): string
    {
        $name = $this->name;
        if (is_string($name)) {
            $name = json_decode($name, true);
        }
        $locale = app()->getLocale();
        $lang = in_array($locale, ['de', 'fr', 'it']) ? $locale : 'de';
        return $name[$lang] ?? $name['de'] ?? '';
    }
}
