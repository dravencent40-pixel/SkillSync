<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefenseSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'status',
        'comprehension_score',
        'feedback',
        'ai_assisted',
        'answered_at',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_assisted' => 'boolean',
            'answered_at' => 'datetime',
            'evaluated_at' => 'datetime',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(DefenseQuestion::class, 'session_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }
}
