<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zipcode extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'commune_id' => 'integer',
    ];

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }
}
