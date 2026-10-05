<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Lid of beheerder (role = admin).
 *
 * @property string $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $coaching_status
 * @property string $referral_code
 * @property string $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids;

    public const COACHING_STATUSES = ['geen', 'aangevraagd', 'actief', 'gepauzeerd', 'gestopt'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'totp_secret', 'remember_token'];

    // Geen "onthoud mij": sessies lopen via de sessietabel.
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'marketing_opt_in' => 'boolean',
            'referral_reward_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'totp_enabled_at' => 'datetime',
            'totp_last_step' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function intake(): HasOne
    {
        return $this->hasOne(Intake::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(RecoveryCode::class);
    }
}
