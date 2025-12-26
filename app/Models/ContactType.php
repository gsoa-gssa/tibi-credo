<?php

namespace App\Models;

use App\Models\Scopes\SignatureCollectionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'subject_de',
        'subject_fr',
        'subject_it',
        'body_de',
        'body_fr',
        'body_it'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
