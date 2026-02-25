<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'salary',
        'deadline',
        'open_position',
        'location',
        'skills',
        'experience',
        'type',
        'category_id',
        'employer_id',
        'isFeatured',
        'status'
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }
    public function applications(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            $job->slug = Str::slug($job->title) . '-' . uniqid();
        });
    }

    protected $casts = [
        'status' => 'boolean',
        'isFeatured' => 'boolean',
    ];

}
