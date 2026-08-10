<?php

namespace App\Modules\Shared\Support;

/**
 * Safely parse sidebar access_permissions expressions without eval.
 * Example: "('student_report', 'can_view') || ('student', 'can_view')"
 */
class MenuPermissionParser
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public function parse(string $expression): array
    {
        $pairs = [];

        if (preg_match_all("/\(\s*'([^']+)'\s*,\s*'([^']+)'\s*\)/", $expression, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $pairs[] = [$match[1], $match[2]];
            }
        }

        return $pairs;
    }

    /**
     * @param  callable(string, string): bool  $checker
     */
    public function evaluate(string $expression, callable $checker): bool
    {
        $expression = trim($expression);

        if ($expression === '') {
            return false;
        }

        $parts = preg_split('/\s*\|\|\s*/', $expression) ?: [];

        foreach ($parts as $part) {
            $andParts = preg_split('/\s*&&\s*/', $part) ?: [];
            $andOk = true;

            foreach ($andParts as $andPart) {
                $pairs = $this->parse($andPart);
                if ($pairs === []) {
                    $andOk = false;
                    break;
                }
                foreach ($pairs as [$category, $ability]) {
                    if (! $checker($category, $ability)) {
                        $andOk = false;
                        break 2;
                    }
                }
            }

            if ($andOk) {
                return true;
            }
        }

        return false;
    }
}
