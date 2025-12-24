<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Get all canton labels (localized name only), keyed by canton ID, cached.
     */
    public static function labels(): array
    {
        return Cache::rememberForever('canton_labels', function () {
            return self::query()
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (self $canton) => [$canton->id => $canton->label])
                ->toArray();
        });
    }
}
