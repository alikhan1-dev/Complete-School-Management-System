<?php

namespace App\Modules\Settings\Support;

use DateTime;
use DateTimeZone;

/**
 * CI Customlib lists used by Schsettings::index (general setting form).
 */
final class SchSettingLists
{
    /**
     * @return array<string, string>
     */
    public static function currencyFormats(): array
    {
        return [
            '####.##' => '12345678.00',
            '#,###.##' => '12,345,678.00',
            '#,##,###.##' => '1,23,45,678.00',
            '#.###.##' => '12.345.678.00',
            '#.###,##' => '12.345.678,00',
            '# ###.##' => '12 345 678.00 ('.__('system.not_for_rtl').')',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function currencyPlaces(): array
    {
        return [
            'before_number' => __('system.before_number'),
            'after_number' => __('system.after_number'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateFormats(): array
    {
        return [
            'd-m-Y' => 'dd-mm-yyyy',
            'd-M-Y' => 'dd-mmm-yyyy',
            'd/m/Y' => 'dd/mm/yyyy',
            'd.m.Y' => 'dd.mm.yyyy',
            'm-d-Y' => 'mm-dd-yyyy',
            'm/d/Y' => 'mm/dd/yyyy',
            'm.d.Y' => 'mm.dd.yyyy',
            'Y/m/d' => 'yyyy/mm/dd',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => __('system.january'),
            2 => __('system.february'),
            3 => __('system.march'),
            4 => __('system.april'),
            5 => __('system.may'),
            6 => __('system.june'),
            7 => __('system.july'),
            8 => __('system.august'),
            9 => __('system.september'),
            10 => __('system.october'),
            11 => __('system.november'),
            12 => __('system.december'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function days(): array
    {
        return [
            'Monday' => __('system.monday'),
            'Tuesday' => __('system.tuesday'),
            'Wednesday' => __('system.wednesday'),
            'Thursday' => __('system.thursday'),
            'Friday' => __('system.friday'),
            'Saturday' => __('system.saturday'),
            'Sunday' => __('system.sunday'),
        ];
    }

    /**
     * CI Customlib::timezone_list.
     *
     * @return array<string, string>
     */
    public static function timezones(): array
    {
        $timezones = [];
        $offsets = [];
        $now = new DateTime('now', new DateTimeZone('UTC'));

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $now->setTimezone(new DateTimeZone($timezone));
            $offset = $now->getOffset();
            $offsets[] = $offset;
            $timezones[$timezone] = '('.self::formatGmtOffset($offset).') '.self::formatTimezoneName($timezone);
        }

        array_multisort($offsets, $timezones);

        return $timezones;
    }

    protected static function formatGmtOffset(int $offset): string
    {
        $hours = intval($offset / 3600);
        $minutes = abs(intval($offset % 3600 / 60));

        return 'GMT'.($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
    }

    protected static function formatTimezoneName(string $name): string
    {
        $name = str_replace('/', ', ', $name);
        $name = str_replace('_', ' ', $name);

        return str_replace('St ', 'St. ', $name);
    }
}
