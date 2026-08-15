<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'avatar_initial',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Kolom password di tabel legacy bernama `password_hash`,
     * bukan `password` — Laravel Auth dipetakan lewat method ini.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /** Arahkan rehash password (Laravel otomatis) ke kolom legacy. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function skillProfile(): HasOne
    {
        return $this->hasOne(SkillProfile::class);
    }
}
