<?php

namespace Modules\Series\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id',
        'title',
        'url',
        'description',
        'duration',
        'order',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
