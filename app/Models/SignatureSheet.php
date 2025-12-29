<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\SignatureCollectionScope;

class SignatureSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'signature_collection_id',
        'short_name',
        'description_internal',
        'sheet_pdf',
        'source_x',
        'source_y',
        'source_font_size',
    ];

    public function signatureCollection(): BelongsTo
    {
        return $this->belongsTo(SignatureCollection::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class, 'signature_collection_id', 'signature_collection_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new SignatureCollectionScope);
    }
}
