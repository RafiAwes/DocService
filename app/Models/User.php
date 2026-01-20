<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
        'address',
        'profile_pic',
        'verification_code',
        'verification_expires_at',
        'email_verified_at',
        'reset_token_expires_at',
        'reset_token',
        'reset_token_verified',
        'reset_token_verified_at',
        'ban_type',
        'banned_until',
        'ban_reason',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'reset_token_expires_at' => 'datetime', 
        'banned_until' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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

    protected function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get the user's profile picture URL.
     * Returns default image if no profile picture is set.
     */
    public function getProfilePicAttribute($value)
    {
        return $value ? url($value) : url('/images/default/user.png');
    }
}
