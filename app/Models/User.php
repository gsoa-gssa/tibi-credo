<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'signature_collection_id',
    ];
    /**
     * Get the signature collection associated with the user.
     */
    public function signatureCollection() : BelongsTo
    {
        return $this->belongsTo(\App\Models\SignatureCollection::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved' => 'boolean'
        ];
    }

    /**
     * Determine if user is approved
     */
    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * Determine if user can access filament by checking if email domain is in the allowed list
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->hasRole("super_admin") || $this->isApproved();
    }
}
