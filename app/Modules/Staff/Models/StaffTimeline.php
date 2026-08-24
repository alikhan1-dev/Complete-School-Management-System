<?php

namespace App\Modules\Staff\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTimeline extends BaseModel
{
    protected $table = 'staff_timeline';

    public $timestamps = true;

    protected $fillable = [
        'staff_id',
        'title',
        'timeline_date',
        'description',
        'document',
        'status',
        'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
