<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'submission_type',
        'rubric_criteria',
    ];

    protected function casts(): array
    {
        return [
            'rubric_criteria' => 'array',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'category_id');
    }
}
