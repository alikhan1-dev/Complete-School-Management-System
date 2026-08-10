<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\PortalUser;
use App\Modules\Auth\Services\LegacyPasswordVerifier;
use App\Modules\Staff\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_staff_login_page_is_reachable(): void
    {
        $this->get('/site/login')->assertOk();
    }

    public function test_student_parent_login_page_is_reachable(): void
    {
        $this->get('/site/userlogin')->assertOk();
    }

    public function test_legacy_password_verifier_accepts_plaintext_and_bcrypt(): void
    {
        $verifier = new LegacyPasswordVerifier();

        $this->assertTrue($verifier->check('secret', 'secret'));
        $this->assertTrue($verifier->check('secret', $verifier->hash('secret')));
        $this->assertTrue($verifier->check('abc', md5('abc')));
        $this->assertFalse($verifier->check('wrong', 'secret'));
    }

    public function test_admin_dashboard_requires_staff_auth(): void
    {
        $this->get('/admin/admin/dashboard')->assertRedirect('/site/login');
    }

    public function test_student_dashboard_requires_portal_auth(): void
    {
        $this->get('/user/user/dashboard')->assertRedirect('/site/userlogin');
    }
}
