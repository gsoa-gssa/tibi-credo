<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    public $casts = [
        'label' => 'json',
    ];

    /**
     * Get the sheets for the source.
     */
    public function sheets()
    {
        return $this->hasMany(Sheet::class);
    }
}
