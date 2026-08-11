<?php

namespace App\Modules\Academics\Support;

/**
 * Mirrors CI application/config/custom_filed-config.php
 */
final class CustomFieldConfig
{
    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'input' => 'Input',
            'number' => 'Number',
            'textarea' => 'Textarea',
            'select' => 'Dropdown',
            'multiselect' => 'Multi Select',
            'checkbox' => 'Checkbox',
            'date_picker' => 'Date Picker',
            'date_picker_time' => 'Datetime Picker',
            'colorpicker' => 'Color Picker',
            'link' => 'Hyperlink',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tables(): array
    {
        return [
            'students' => 'Student',
            'staff' => 'Staff',
            'transfer_certificate' => 'Transfer Certificate',
        ];
    }

    /**
     * Types that require field_values (CI validate_type callback).
     *
     * @return list<string>
     */
    public static function typesRequiringValues(): array
    {
        return ['select', 'multiselect', 'checkbox', 'link'];
    }
}
