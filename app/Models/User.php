<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

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
        ];
    }

    public function sheets()
    {
        return $this->hasMany(Sheet::class);
    }

    /**
     * Get last Sheet label and add 1, determining the next Sheet Label
     */
    public function getNextSheetLabel()
    {
        $lastSheet = $this->sheets()->latest()->first();
        return $lastSheet ? $lastSheet->label + 1 : 1;
    }

    /**
     * Determine if the user has submitted a sheet in the last 30 minutes and if so, return the commune id
     */
    public function getCommuneId()
    {
        $lastSheet = $this->sheets()->latest()->first();
        return $lastSheet && $lastSheet->created_at->gt(now()->subMinutes(30)) ? $lastSheet->commune_id : null;
    }

    /**
     * Determine if user can access filament by checking if email domain is in the allowed list
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return in_array(explode('@', $this->email)[1], config('filament.allowed_domains'));
    }
}
