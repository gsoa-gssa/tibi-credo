<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SignatureCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_name',
        'official_name_de',
        'official_name_fr',
        'official_name_it',
        'publication_date',
        'end_date',
        'responsible_person_name_de',
        'responsible_person_email_de',
        'responsible_person_phone_de',
        'responsible_person_name_fr',
        'responsible_person_email_fr',
        'responsible_person_phone_fr',
        'responsible_person_name_it',
        'responsible_person_email_it',
        'responsible_person_phone_it',
    ];

    public function signatureSheets(): HasMany
    {
        return $this->hasMany(SignatureSheet::class);
    }
}
