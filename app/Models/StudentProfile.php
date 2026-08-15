<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nis',
        'sekolah',
        'jurusan',
        'kelas',
        'bio',
        'github_url',
        'cv_path',
        'cv_original_name',
        'cv_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'cv_uploaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
