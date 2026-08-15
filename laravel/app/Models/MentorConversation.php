<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorConversation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'user_id',
        'title',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(MentorMessage::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
