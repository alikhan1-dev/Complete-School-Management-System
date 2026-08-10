<?php

namespace Tests\Feature\Roles;

use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Support\MenuPermissionParser;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    public function test_menu_permission_parser_extracts_pairs(): void
    {
        $parser = new MenuPermissionParser();
        $pairs = $parser->parse("('student_report', 'can_view') || ('student', 'can_view')");

        $this->assertCount(2, $pairs);
        $this->assertSame(['student_report', 'can_view'], $pairs[0]);
        $this->assertSame(['student', 'can_view'], $pairs[1]);
    }

    public function test_menu_permission_parser_evaluates_or_expression(): void
    {
        $parser = new MenuPermissionParser();
        $result = $parser->evaluate("('a', 'can_view') || ('b', 'can_view')", function ($cat, $ability) {
            return $cat === 'b' && $ability === 'can_view';
        });

        $this->assertTrue($result);
    }

    public function test_permission_service_denies_when_guest(): void
    {
        $service = app(PermissionService::class);
        $this->assertFalse($service->hasPrivilege('student', 'can_view'));
    }
}
