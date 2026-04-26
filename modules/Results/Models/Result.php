<?php

namespace Modules\Results\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'series_id',
        'tenant_id',
        'score',
        'total_questions',
        'correct_answers',
        'percentage',
        'attempt_number',
        'answers_snapshot',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers_snapshot' => 'array',
            'completed_at'     => 'datetime',
            'percentage'       => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(\Modules\Series\Models\Series::class);
    }
}
