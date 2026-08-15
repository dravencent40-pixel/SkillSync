<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefenseQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'order_index',
        'question',
        'answer',
        'answer_score',
        'answer_feedback',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DefenseSession::class, 'session_id');
    }
}
