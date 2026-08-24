<?php

namespace Tests\Unit\Timetable;

use App\Modules\Timetable\Services\ClassTimetableService;
use InvalidArgumentException;
use Tests\TestCase;

class ClassTimetableQuickPeriodTest extends TestCase
{
    public function test_generate_period_slots_matches_ci_sequence(): void
    {
        $service = app(ClassTimetableService::class);

        $slots = $service->generatePeriodSlots('08:00', 45, 15, 3);

        $this->assertSame([
            ['time_from' => '08:00', 'time_to' => '08:45'],
            ['time_from' => '09:00', 'time_to' => '09:45'],
            ['time_from' => '10:00', 'time_to' => '10:45'],
        ], $slots);
    }

    public function test_generate_period_slots_rejects_invalid_duration(): void
    {
        $service = app(ClassTimetableService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->generatePeriodSlots('08:00', 0, 0, 1);
    }

    public function test_generate_period_slots_rejects_invalid_start_time(): void
    {
        $service = app(ClassTimetableService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->generatePeriodSlots('not-a-time', 45, 0, 1);
    }
}
