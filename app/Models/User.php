<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Role helpers
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(\Modules\Results\Models\Result::class);
    }

    public function examRequests(): HasMany
    {
        return $this->hasMany(\Modules\ExamRequests\Models\ExamRequest::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(\Modules\Series\Models\StudentSeriesProgress::class);
    }
}
