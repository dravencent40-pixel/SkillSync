<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillProfileTrack extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'category_id',
        'overall_score',
        'criterion1_score',
        'criterion2_score',
        'criterion3_score',
        'comprehension_avg',
        'tasks_completed',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }
}
