<?php

namespace App\Modules\Students\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDoc extends BaseModel
{
    protected $table = 'student_doc';

    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'title',
        'doc',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
