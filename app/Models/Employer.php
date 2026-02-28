<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    use HasFactory;

     protected $table = 'employer_profiles';

    protected $fillable = [
        'user_id',
        'company_name',
        'slogan',
        'description',
        'company_type',
        'technologies_using',
        'is_Verified'
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'is_Verified' => 'boolean',
    ];
}
