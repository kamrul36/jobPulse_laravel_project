<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobseeker extends Model
{
    use HasFactory;

    protected $table = 'jobseeker_profiles';


    protected $fillable = [
        'user_id',
        'resume_file',
        'cover_letter',
        'gender',
        'skills',
        'qualification',
        'title'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
