<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * CI table spelling preserved: attendence_type.
 */
class AttendenceType extends BaseModel
{
    protected $table = 'attendence_type';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'key_value',
        'long_lang_name',
        'long_name_style',
        'is_active',
        'for_qr_attendance',
        'for_schedule',
    ];

    /**
     * CI Attendencetype_model::get (active only).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 'yes')->orderBy('id');
    }
}
