<?php

namespace App\Modules\Students\Models;

use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends BaseModel
{
    protected $table = 'students';

    protected $guarded = [];

    public function sessions(): HasMany
    {
        return $this->hasMany(StudentSession::class, 'student_id');
    }

    public function currentSessionRecord(): HasOne
    {
        return $this->hasOne(StudentSession::class, 'student_id');
    }

    public function fullName(bool $withMiddle = true, bool $withLast = true): string
    {
        $parts = [trim((string) $this->firstname)];
        if ($withMiddle && filled($this->middlename)) {
            $parts[] = trim((string) $this->middlename);
        }
        if ($withLast && filled($this->lastname)) {
            $parts[] = trim((string) $this->lastname);
        }

        return trim(implode(' ', array_filter($parts)));
    }
}
