<?php

namespace App\Modules\Fees\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class FeeGroup extends BaseModel
{
    protected $table = 'fee_groups';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'is_system',
        'nature',
        'is_active',
    ];

    /**
     * CI Feegroup_model::get — exclude system + custom natures.
     */
    public function scopeAdminList(Builder $query): Builder
    {
        return $query
            ->where('is_system', 0)
            ->where('nature', '!=', 'custom')
            ->orderBy('id');
    }
}
