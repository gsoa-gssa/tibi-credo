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
}
