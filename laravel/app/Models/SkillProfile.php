<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'overall_score',
        'clean_code_avg',
        'security_avg',
        'efficiency_avg',
        'comprehension_avg',
        'tasks_completed',
        'badge',
        'strengths',
        'weaknesses',
        'narrative',
        'is_public',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
