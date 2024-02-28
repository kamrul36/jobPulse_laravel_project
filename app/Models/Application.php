<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    public function job(): BelongsTo {
        return $this->belongsTo(Job::class);
    }

    public function jobseeker():BelongsTo {
        return $this->belongsTo(Jobseeker::class);
    }
}
