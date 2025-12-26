<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
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
        'contact_type_id'
    ];

    protected $casts = [
        'birthdate' => 'date',
        'letter_sent' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
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
