<?php

namespace Modules\ExamRequests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'status',
        'manager_id',
        'manager_recommendation',
        'manager_notes',
        'manager_reviewed_at',
        'owner_id',
        'owner_decision',
        'owner_notes',
        'owner_decided_at',
        'exam_duration_days',
        'exam_date',
    ];

    protected function casts(): array
    {
        return [
            'manager_reviewed_at' => 'datetime',
            'owner_decided_at'    => 'datetime',
            'exam_date'           => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }
}
