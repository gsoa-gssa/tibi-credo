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
    /**
     * Override the save method to enforce business rules:
     * - Only super_admin can assign super_admin role
     * - signature_collection_id must match current user's if not super_admin
     */
    public function save(array $options = [])
    {
        $currentUser = auth()->user();
        if ($currentUser && !$currentUser->hasRole('super_admin')) {
            // Prevent assigning super_admin role
            if ($this->hasRole('super_admin') || (isset($this->roles) && in_array('super_admin', (array)$this->roles))) {
                $this->syncRoles(array_diff((array)$this->getRoleNames(), ['super_admin']));
            }
            // Enforce signature_collection_id
            $this->signature_collection_id = $currentUser->signature_collection_id;
        }
        if (empty($this->signature_collection_id)) {
            throw new \Exception('signature_collection_id cannot be null');
        }
        return parent::save($options);
    }
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
        'login_code',
        'login_code_expiration',
        'login_code_valid_ip',
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
            'approved' => 'boolean',
            'login_code_expiration' => 'datetime',
        ];
    }
    
    public function generateLoginCodeForAdminIP(string $ip): string
    {
        // 6-char alphanumeric, uppercase; valid 30 seconds
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $this->login_code = $code;
        $this->login_code_expiration = now()->addSeconds(30);
        $this->login_code_valid_ip = $ip;
        $this->save();
        return $code;
    }

    /**
     * Determine if user is approved
     */
    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * Determine if user can access filament panel
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->hasRole("super_admin") || $this->isApproved();
    }
}
