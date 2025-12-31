<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchKind extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_name_de',
        'short_name_fr',
        'short_name_it',
        'subject_de',
        'subject_fr',
        'subject_it',
        'body_de',
        'body_fr',
        'body_it',
    ];

    public function sendBatches(): HasMany
    {
        return $this->hasMany(Batch::class, 'send_kind');
    }

    public function receiveBatches(): HasMany
    {
        return $this->hasMany(Batch::class, 'receive_kind');
    }

    public function signatureCollectionsDefaultFor()
    {
        return $this->hasMany(SignatureCollection::class, 'default_send_kind_id');
    }

    public function getShortNameAttribute()
    {
        $lang = app()->getLocale();
        return $this->{'short_name_' . $lang} ?? $this->short_name_de;
    }
}
