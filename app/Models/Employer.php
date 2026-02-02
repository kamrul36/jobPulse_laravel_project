<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    use HasFactory;

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    protected $casts = [
        'status' => 'boolean',
        'isFeatued' => 'boolean',
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];
}
