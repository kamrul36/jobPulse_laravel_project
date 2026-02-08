<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        // 'firstName',
        // 'lastName',
        // 'email',
        // 'mobile',
        // 'password',

        'username',
        'name',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'password',
        'role_id',
        'otp',
        'otp_expires_at',
        'is_active',

    ];

    protected $attributes = ['otp' => '0'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'otp',
        'otp_expires_at',
        // 'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Helper methods
    public function hasRole($role)
    {
        return $this->role->slug === $role;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function isEmailVerified()
    {
        return !is_null($this->email_verified_at);
    }

    public function isPhoneVerified()
    {
        return !is_null($this->phone_verified_at);
    }
}
