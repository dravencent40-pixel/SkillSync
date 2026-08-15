<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'sender',
        'message',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MentorConversation::class, 'conversation_id');
    }
}
