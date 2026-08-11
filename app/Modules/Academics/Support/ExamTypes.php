<?php

namespace App\Modules\Academics\Support;

/**
 * Mirrors CI config/app-config.php exam_type keys (labels can be localized later).
 */
final class ExamTypes
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'basic_system' => 'Basic System',
            'school_grade_system' => 'School Grade System',
            'coll_grade_system' => 'College Grade System',
            'gpa' => 'GPA Grading System',
            'average_passing' => 'Average Passing',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }
}
