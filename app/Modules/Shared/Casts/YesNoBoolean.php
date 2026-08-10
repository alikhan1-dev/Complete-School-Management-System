<?php

namespace App\Modules\Shared\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast legacy varchar is_active values ('yes'/'no') to boolean.
 */
class YesNoBoolean implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);

        return in_array($normalized, ['yes', '1', 'true'], true);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no';
    }
}
