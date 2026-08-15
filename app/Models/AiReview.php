<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReview extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'clean_code_score',
        'security_score',
        'efficiency_score',
        'overall_score',
        'summary',
        'findings_json',
    ];

    protected function casts(): array
    {
        return [
            'findings_json' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }
}
