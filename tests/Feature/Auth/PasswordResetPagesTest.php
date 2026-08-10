<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordResetPagesTest extends TestCase
{
    public function test_staff_forgot_password_page_is_reachable(): void
    {
        $this->get('/site/forgotpassword')->assertOk();
    }

    public function test_portal_forgot_password_page_is_reachable(): void
    {
        $this->get('/site/ufpassword')->assertOk();
    }
}
