<?php

namespace Modules\Evaluations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEvaluation extends Model
{
    protected $table = 'student_evaluations';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'parking_score',
        'reverse_score',
        'city_driving_score',
        'steering_score',
        'rules_score',
        'confidence_score',
        'comment',
        'final_status',
        'evaluated_by',
    ];

    protected $casts = [
        'parking_score'      => 'integer',
        'reverse_score'      => 'integer',
        'city_driving_score' => 'integer',
        'steering_score'     => 'integer',
        'rules_score'        => 'integer',
        'confidence_score'   => 'integer',
    ];

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'evaluated_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'student_id');
    }

    /** Average of all non-null skill scores */
    public function getAverageScoreAttribute(): ?float
    {
        $scores = array_filter([
            $this->parking_score,
            $this->reverse_score,
            $this->city_driving_score,
            $this->steering_score,
            $this->rules_score,
            $this->confidence_score,
        ], fn($v) => $v !== null);

        return count($scores) ? round(array_sum($scores) / count($scores), 1) : null;
    }
}
