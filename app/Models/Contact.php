<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    /**
     * Get Sheet
     */
    public function sheet()
    {
        return $this->belongsTo(Sheet::class);
    }
}
