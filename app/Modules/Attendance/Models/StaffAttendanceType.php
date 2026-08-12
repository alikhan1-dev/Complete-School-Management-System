<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * CI table: staff_attendance_type.
 */
class StaffAttendanceType extends BaseModel
{
    protected $table = 'staff_attendance_type';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'key_value',
        'is_active',
        'for_qr_attendance',
        'long_lang_name',
        'long_name_style',
        'for_schedule',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 'yes')->orderBy('id');
    }
}
