<?php

namespace Modules\Series\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'thumbnail',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function results(): HasMany
    {
        return $this->hasMany(\Modules\Results\Models\Result::class);
    }

    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentSeriesProgress::class);
    }
}
