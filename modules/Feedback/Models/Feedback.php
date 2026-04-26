<?php

namespace Modules\Feedback\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'staff_id',
        'rating',
        'comment',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
