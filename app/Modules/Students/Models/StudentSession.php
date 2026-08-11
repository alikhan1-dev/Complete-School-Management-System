<?php

namespace App\Modules\Students\Models;

use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSession extends BaseModel
{
    protected $table = 'student_session';

    protected $fillable = [
        'session_id',
        'student_id',
        'class_id',
        'section_id',
        'hostel_room_id',
        'vehroute_id',
        'route_pickup_point_id',
        'transport_fees',
        'fees_discount',
        'is_leave',
        'is_active',
        'is_alumni',
        'default_login',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
