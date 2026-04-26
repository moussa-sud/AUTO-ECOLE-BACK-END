<?php

namespace Modules\Series\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSeriesProgress extends Model
{
    use HasFactory;

    protected $table = 'student_series_progress';

    protected $fillable = [
        'user_id',
        'series_id',
        'tenant_id',
        'video_watched',
        'video_watched_at',
        'quiz_completed',
        'quiz_completed_at',
        'best_score',
        'attempts_count',
    ];

    protected function casts(): array
    {
        return [
            'video_watched'     => 'boolean',
            'video_watched_at'  => 'datetime',
            'quiz_completed'    => 'boolean',
            'quiz_completed_at' => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
