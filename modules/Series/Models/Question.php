<?php

namespace Modules\Series\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id',
        'question_text',
        'image',
        'order',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function correctAnswer()
    {
        return $this->hasOne(Answer::class)->where('is_correct', true);
    }
}
