<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'created_by',
        'title',
        'slug',
        'industry_context',
        'case_brief',
        'starter_code',
        'difficulty',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'task_id');
    }
}
