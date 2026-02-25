<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    public function employer():BelongsTo {
        return $this->belongsTo(Employer::class);
    }
    public function applications():BelongsTo {
        return $this->belongsTo(Application::class);
    }

    public function category():BelongsTo {
        return $this->belongsTo(Category::class, 'category_id');
    }

    protected $casts = [
        'status' => 'boolean',
        'isFeatured' => 'boolean',
    ];

}
