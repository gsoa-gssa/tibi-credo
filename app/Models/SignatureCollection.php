<?php

namespace App\Models;

use App\Enums\SignatureCollectionType;
use App\Traits\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SignatureCollection extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = [
        'type',
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
        'return_workdays',
        'return_address_letters',
        'return_address_parcels',
        'pp_sender_zipcode',
        'pp_sender_place_de',
        'pp_sender_place_fr',
        'pp_sender_place_it',
        'pp_sender_name_de',
        'pp_sender_name_fr',
        'pp_sender_name_it',
        'color',
    ];

    protected $casts = [
        'type' => SignatureCollectionType::class,
        'publication_date' => 'date',
        'end_date' => 'date',
        'return_workdays' => 'integer',
    ];

    public function signatureSheets(): HasMany
    {
        return $this->hasMany(SignatureSheet::class);
    }
}
