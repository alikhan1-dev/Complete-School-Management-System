<?php

namespace App\Modules\Students\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTimeline extends BaseModel
{
    protected $table = 'student_timeline';

    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'title',
        'timeline_date',
        'description',
        'document',
        'status',
        'created_student_id',
        'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
