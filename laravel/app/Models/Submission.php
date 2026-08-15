<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'user_id',
        'language',
        'code_content',
        'file_path',
        'external_link',
        'notes',
        'status',
        'submitted_at',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(AiReview::class, 'submission_id');
    }

    public function defenseSession(): HasOne
    {
        return $this->hasOne(DefenseSession::class, 'submission_id');
    }
}
