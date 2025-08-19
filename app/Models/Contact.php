<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'lastname',
        'street_no',
        'zipcode_id',
        'birthdate',
        'sheet_id',
        'contact_type_id'
    ];

    protected $casts = [
        'birthdate' => 'date',
        'letter_sent' => 'datetime',
    ];

    /**
     * Get Sheet
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class);
    }

    /**
     * Get Zipcode
     */
    public function zipcode(): BelongsTo
    {
        return $this->belongsTo(Zipcode::class);
    }

    /**
     * Get Contact Type
     */
    public function contactType(): BelongsTo
    {
        return $this->belongsTo(ContactType::class);
    }
}
