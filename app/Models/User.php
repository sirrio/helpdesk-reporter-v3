<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'isMod', 'isAdmin', 'email_verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'isMod' => 'boolean',
            'isAdmin' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'deleted_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * Get the attendances recorded for the user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Irreversibly remove personal account data while preserving references.
     */
    public function anonymize(): void
    {
        if (! $this->trashed() || $this->anonymized_at !== null) {
            return;
        }

        $this->forceFill([
            'name' => 'Anonymisierter Account',
            'email' => 'anonymized-'.Str::uuid().'@example.invalid',
            'password' => Str::random(64),
            'email_verified_at' => null,
            'remember_token' => null,
            'isMod' => false,
            'isAdmin' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'anonymized_at' => now(),
        ])->save();
    }
}
