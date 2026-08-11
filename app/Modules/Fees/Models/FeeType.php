<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class FeeType extends BaseModel
{
    protected $table = 'feetype';

    public $timestamps = true;

    protected $fillable = [
        'type',
        'code',
        'description',
        'is_system',
        'nature',
        'is_active',
        'feecategory_id',
        'session_id',
        'student_session_id',
    ];

    /**
     * CI Feetype_model::get — exclude system + custom natures.
     */
    public function scopeAdminList(Builder $query): Builder
    {
        return $query
            ->where('is_system', 0)
            ->where('nature', '!=', 'custom')
            ->orderBy('id');
    }
}
