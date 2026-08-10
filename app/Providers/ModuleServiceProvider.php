<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Explicit module providers (keeps discovery simple and predictable).
     *
     * @var list<class-string>
     */
    protected array $modules = [
        \App\Modules\Shared\SharedServiceProvider::class,
        \App\Modules\Auth\AuthServiceProvider::class,
        \App\Modules\Roles\RolesServiceProvider::class,
        \App\Modules\Staff\StaffServiceProvider::class,
        \App\Modules\Academics\AcademicsServiceProvider::class,
        \App\Modules\Students\StudentsServiceProvider::class,
        \App\Modules\Parents\ParentsServiceProvider::class,
        \App\Modules\Attendance\AttendanceServiceProvider::class,
        \App\Modules\Fees\FeesServiceProvider::class,
        \App\Modules\Exams\ExamsServiceProvider::class,
        \App\Modules\OnlineExam\OnlineExamServiceProvider::class,
        \App\Modules\Timetable\TimetableServiceProvider::class,
        \App\Modules\Homework\HomeworkServiceProvider::class,
        \App\Modules\LessonPlan\LessonPlanServiceProvider::class,
        \App\Modules\Library\LibraryServiceProvider::class,
        \App\Modules\Transport\TransportServiceProvider::class,
        \App\Modules\Hostel\HostelServiceProvider::class,
        \App\Modules\Inventory\InventoryServiceProvider::class,
        \App\Modules\Payroll\PayrollServiceProvider::class,
        \App\Modules\Leave\LeaveServiceProvider::class,
        \App\Modules\Finance\FinanceServiceProvider::class,
        \App\Modules\Communication\CommunicationServiceProvider::class,
        \App\Modules\Chat\ChatServiceProvider::class,
        \App\Modules\FrontCms\FrontCmsServiceProvider::class,
        \App\Modules\FrontOffice\FrontOfficeServiceProvider::class,
        \App\Modules\OnlineAdmission\OnlineAdmissionServiceProvider::class,
        \App\Modules\Certificates\CertificatesServiceProvider::class,
        \App\Modules\Reports\ReportsServiceProvider::class,
        \App\Modules\Settings\SettingsServiceProvider::class,
        \App\Modules\Content\ContentServiceProvider::class,
        \App\Modules\Payments\PaymentsServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->modules as $provider) {
            $this->app->register($provider);
        }
    }
}
