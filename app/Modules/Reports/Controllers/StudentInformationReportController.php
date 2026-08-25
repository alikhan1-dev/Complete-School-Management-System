<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\AlumniReportService;
use App\Modules\Reports\Services\StudentInformationReportService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\ClassTeacherScopeService;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI Report student information hub + student / class-section / ratio / guardian / history / login / class-subject / admission / sibling / profile / online admission / alumni reports.
 */
class StudentInformationReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentInformationReportService $reports,
        protected AlumniReportService $alumniReports,
        protected ClassTeacherScopeService $classTeacherScope,
    ) {
    }

    public function studentinformation(): View
    {
        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_information_report'),
            'contentView' => 'reports::admin.student_information.hub',
        ], $this->navFlags()));
    }

    public function studentreport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'category_id' => $request->input('category_id', ''),
            'gender' => $request->input('gender', ''),
            'rte' => $request->input('rte', ''),
        ];
        $rows = collect();
        $searched = $request->isMethod('post') || $request->filled('class_id');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass(
                    (int) $filters['class_id'],
                    $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
                ),
                403
            );
            $rows = $this->reports->studentReportRows(
                (int) $filters['class_id'],
                $filters['section_id'] !== '' ? (int) $filters['section_id'] : null,
                $filters['category_id'] !== '' ? (int) $filters['category_id'] : null,
                $filters['gender'] !== '' ? (string) $filters['gender'] : null,
                $filters['rte'] !== '' ? (string) $filters['rte'] : null,
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_report'),
            'contentView' => 'reports::admin.student_information.student_report',
            'classes' => $this->reports->classes(),
            'categories' => $this->reports->categories(),
            'genders' => $this->reports->genders(),
            'rteStatuses' => $this->reports->rteStatuses(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function studentreportvalidation(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_report', 'can_view'), 403);

        $searchType = (string) $request->input('search_type', 'search_filter');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $categoryId = $request->input('category_id');
        $gender = $request->input('gender');
        $rte = $request->input('rte');

        if ($searchType === 'search_filter') {
            if ($classId === null || $classId === '') {
                return response()->json([
                    'status' => 0,
                    'error' => [
                        'class_id' => '<p>The Class field is required.</p>',
                    ],
                ]);
            }
        }

        return response()->json([
            'status' => 1,
            'error' => '',
            'params' => [
                'srch_type' => $searchType === 'search_filter' ? 'search_filter' : 'search_full',
                'class_id' => $classId,
                'section_id' => $sectionId,
                'category_id' => $categoryId,
                'gender' => $gender,
                'rte' => $rte,
            ],
        ]);
    }

    public function dtstudentreportlist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_report', 'can_view'), 403);
        $payload = $this->reports->studentReportDataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function classsectionreport(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_report', 'can_view'), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.class_section_report'),
            'contentView' => 'reports::admin.student_information.class_section',
            'class_section_list' => $this->reports->classSectionCounts(),
        ], $this->navFlags()));
    }

    public function boys_girls_ratio(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_gender_ratio_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_gender_ratio_report'),
            'contentView' => 'reports::admin.student_information.gender_ratio',
            'report' => $this->reports->genderRatioReport(),
        ], $this->navFlags()));
    }

    public function student_teacher_ratio(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_teacher_ratio_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_teacher_ratio_report'),
            'contentView' => 'reports::admin.student_information.teacher_ratio',
            'report' => $this->reports->teacherRatioReport(),
        ], $this->navFlags()));
    }

    public function guardianreport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('guardian_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $rows = collect();
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass((int) $filters['class_id'], (int) $filters['section_id']),
                403
            );
            $rows = $this->reports->guardianRows((int) $filters['class_id'], (int) $filters['section_id']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.guardian_report'),
            'contentView' => 'reports::admin.student_information.guardian',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function admissionreport(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_history', 'can_view'), 403);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_history'),
            'contentView' => 'reports::admin.student_information.history',
            'classes' => $this->reports->classes(),
            'admission_year' => $this->reports->admissionYears(),
            'filters' => ['class_id' => '', 'year' => ''],
            'rows' => collect(),
            'searched' => false,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function admissionreportSearch(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_history', 'can_view'), 403);
        $request->validate([
            'class_id' => ['required'],
        ], [
            'class_id.required' => 'The Class field is required.',
        ]);
        $classId = (int) $request->input('class_id');
        abort_unless($this->reports->canAccessClass($classId), 403);
        $yearRaw = $request->input('year');
        $year = ($yearRaw === null || $yearRaw === '') ? null : (int) $yearRaw;

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_history'),
            'contentView' => 'reports::admin.student_information.history',
            'classes' => $this->reports->classes(),
            'admission_year' => $this->reports->admissionYears(),
            'filters' => [
                'class_id' => $request->input('class_id', ''),
                'year' => $request->input('year', ''),
            ],
            'rows' => $this->reports->historyRows($classId, $year),
            'searched' => true,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function admissionsearchvalidation(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_history', 'can_view'), 403);
        $classId = $request->input('class_id');
        if ($classId === null || $classId === '') {
            return response()->json([
                'status' => 0,
                'error' => [
                    'class_id' => '<p>The Class field is required.</p>',
                ],
            ]);
        }

        return response()->json([
            'status' => 1,
            'error' => '',
            'params' => [
                'class_id' => $classId,
                'year' => $request->input('year'),
            ],
        ]);
    }

    public function dtadmissionreportlist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_history', 'can_view'), 403);
        $payload = $this->reports->historyDataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function logindetailreport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_login_credential_report', 'can_view'), 403);

        return $this->credentialPage($request, 'student');
    }

    public function parentlogindetailreport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_login_credential_report', 'can_view'), 403);

        return $this->credentialPage($request, 'parent');
    }

    public function searchloginvalidation(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_login_credential_report', 'can_view'), 403);
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $error = [];
        if ($classId === null || $classId === '') {
            $error['class_id'] = '<p>The Class field is required.</p>';
        }
        if ($sectionId === null || $sectionId === '') {
            $error['section_id'] = '<p>The Section field is required.</p>';
        }
        if ($error !== []) {
            return response()->json([
                'status' => 0,
                'error' => $error,
            ]);
        }

        return response()->json([
            'status' => 1,
            'error' => '',
            'params' => [
                'class_id' => $classId,
                'section_id' => $sectionId,
            ],
        ]);
    }

    public function dtcredentialreportlist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_login_credential_report', 'can_view'), 403);
        $payload = $this->reports->studentCredentialDataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function dtparentcredentialreportlist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_login_credential_report', 'can_view'), 403);
        $payload = $this->reports->parentCredentialDataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function class_subject(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('class_subject_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $subjects = [];
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass((int) $filters['class_id'], (int) $filters['section_id']),
                403
            );
            $subjects = $this->reports->classSubjectGroups((int) $filters['class_id'], (int) $filters['section_id']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.class_subject_report'),
            'contentView' => 'reports::admin.student_information.class_subject',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'subjects' => $subjects,
            'searched' => $searched,
        ], $this->navFlags()));
    }

    public function admission_report(): View
    {
        abort_unless($this->permissions->hasPrivilege('admission_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.admission_report'),
            'contentView' => 'reports::admin.student_information.admission_report',
            'searchTypes' => $this->reports->searchTypes(),
            'filters' => ['search_type' => '', 'date_from' => '', 'date_to' => ''],
            'rows' => [],
            'searched' => false,
            'filter_label' => '',
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function admission_reportSearch(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('admission_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();
        $request->validate([
            'search_type' => ['required'],
        ], [
            'search_type.required' => 'The Search Type field is required.',
        ]);
        $payload = $this->reports->admissionReportDataTable($request);

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.admission_report'),
            'contentView' => 'reports::admin.student_information.admission_report',
            'searchTypes' => $this->reports->searchTypes(),
            'filters' => [
                'search_type' => $request->input('search_type', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
            'rows' => $payload['data'],
            'searched' => true,
            'filter_label' => $payload['filter_label'],
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function searchreportvalidation(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('admission_report', 'can_view'), 403);
        $searchType = $request->input('search_type');
        if ($searchType === null || $searchType === '') {
            return response()->json([
                'status' => 0,
                'error' => [
                    'search_type' => '<p>The Search Type field is required.</p>',
                ],
            ]);
        }

        $dateFrom = '';
        $dateTo = '';
        if ($searchType === 'period') {
            $dateFrom = (string) $request->input('date_from', '');
            $dateTo = (string) $request->input('date_to', '');
        }

        return response()->json([
            'status' => 1,
            'error' => '',
            'params' => [
                'search_type' => $searchType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function dtadmissionreport(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('admission_report', 'can_view'), 403);
        $payload = $this->reports->admissionReportDataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function sibling_report(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('sibling_report', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $groups = [];
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass((int) $filters['class_id'], (int) $filters['section_id']),
                403
            );
            $groups = $this->reports->siblingGroups((int) $filters['class_id'], (int) $filters['section_id']);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.sibling_report'),
            'contentView' => 'reports::admin.student_information.sibling',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'groups' => $groups,
            'searched' => $searched,
            'reports' => $this->reports,
        ], $this->navFlags()));
    }

    public function student_profile(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('student_profile', 'can_view'), 403);

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'search_type' => $request->input('search_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
        $rows = collect();
        $filterLabel = '';
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass((int) $filters['class_id'], (int) $filters['section_id']),
                403
            );
            $from = null;
            $to = null;
            if ($filters['search_type'] !== '') {
                $range = $this->reports->dateRange(
                    (string) $filters['search_type'],
                    $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                    $filters['date_to'] !== '' ? (string) $filters['date_to'] : null,
                );
                $from = $range['from'];
                $to = $range['to'];
                $filterLabel = $this->reports->filterLabel($from, $to);
            }
            $rows = $this->reports->studentProfileRows((int) $filters['class_id'], (int) $filters['section_id'], $from, $to);
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.student_profile'),
            'contentView' => 'reports::admin.student_information.student_profile',
            'classes' => $this->reports->classes(),
            'searchTypes' => $this->reports->searchTypes(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'filter_label' => $filterLabel,
            'reports' => $this->reports,
            'adm_auto_insert' => $this->reports->admAutoInsert(),
            'customFields' => $this->reports->studentTableCustomFields(),
        ], $this->navFlags()));
    }

    public function online_admission_report(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('online_admission_report', 'can_view'), 403);
        $this->reports->assertHasClassSectionMatrix();

        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
            'status' => $request->input('status', ''),
        ];
        $rows = [];
        $searched = $request->isMethod('post') || $request->filled('search');
        if ($searched) {
            if ($filters['class_id'] !== '') {
                abort_unless(
                    $this->reports->canAccessClass(
                        (int) $filters['class_id'],
                        $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
                    ),
                    403
                );
            }
            $payload = $this->reports->onlineAdmissionDataTable($request);
            $rows = $payload['data'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.online_admission_report'),
            'contentView' => 'reports::admin.student_information.online_admission',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'paymentOn' => $this->reports->onlineAdmissionPaymentEnabled(),
        ], $this->navFlags()));
    }

    public function checkvalidation(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission_report', 'can_view'), 403);

        return response()->json([
            'status' => 1,
            'error' => '',
            'params' => [
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    public function dtonlineadmissionreportlist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('online_admission_report', 'can_view'), 403);
        $payload = $this->reports->onlineAdmissionDataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    /**
     * CI Report::alumnireport — filter by pass-out session + class (+ optional section).
     */
    public function alumnireport(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('alumni_report', 'can_view'), 403);

        $filters = [
            'session_id' => $request->input('session_id', ''),
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $rows = collect();
        $searched = false;

        if ($request->isMethod('post') && $request->input('search') === 'search_filter') {
            $request->validate([
                'session_id' => ['required'],
                'class_id' => ['required'],
            ], [
                'session_id.required' => 'The '.__('system.session').' field is required.',
                'class_id.required' => 'The '.__('system.class').' field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass(
                    (int) $filters['class_id'],
                    $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
                ),
                403
            );
            $searched = true;
            $rows = $this->alumniReports->searchByFilter(
                (int) $filters['session_id'],
                (int) $filters['class_id'],
                $filters['section_id'] !== '' ? (int) $filters['section_id'] : null
            );
        }

        return view('shared::layouts.admin', array_merge([
            'title' => __('system.alumni'),
            'contentView' => 'reports::admin.student_information.alumni_report',
            'sessions' => $this->alumniReports->sessions(),
            'classes' => $this->alumniReports->classes(),
            'sectionOptions' => $this->sectionOptions((int) ($filters['class_id'] ?: 0)),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
            'alumniMap' => $this->alumniReports->alumniDetailsByStudentId(),
            'customFields' => $this->alumniReports->studentTableCustomFields(),
            'reports' => $this->alumniReports,
        ], $this->navFlags()));
    }

    /**
     * @return list<object>
     */
    protected function sectionOptions(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        return $this->classTeacherScope->sectionsForClass($classId);
    }

    /**
     * @param  'student'|'parent'  $kind
     */
    protected function credentialPage(Request $request, string $kind): View
    {
        $filters = [
            'class_id' => $request->input('class_id', ''),
            'section_id' => $request->input('section_id', ''),
        ];
        $rows = [];
        $searched = $request->isMethod('post');
        if ($searched) {
            $request->validate([
                'class_id' => ['required'],
                'section_id' => ['required'],
            ], [
                'class_id.required' => 'The Class field is required.',
                'section_id.required' => 'The Section field is required.',
            ]);
            abort_unless(
                $this->reports->canAccessClass((int) $filters['class_id'], (int) $filters['section_id']),
                403
            );
            $payload = $kind === 'parent'
                ? $this->reports->parentCredentialDataTable($request)
                : $this->reports->studentCredentialDataTable($request);
            $rows = $payload['data'];
        }

        return view('shared::layouts.admin', array_merge([
            'title' => $kind === 'parent'
                ? __('system.parent_login_credential_report')
                : __('system.student_login_credential_report'),
            'contentView' => $kind === 'parent'
                ? 'reports::admin.student_information.parent_login'
                : 'reports::admin.student_information.student_login',
            'classes' => $this->reports->classes(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $searched,
        ], $this->navFlags()));
    }

    /**
     * @return array<string, bool>
     */
    protected function navFlags(): array
    {
        return [
            'canStudentReport' => $this->permissions->hasPrivilege('student_report', 'can_view'),
            'canClassSectionReport' => $this->permissions->hasPrivilege('class_section_report', 'can_view'),
            'canGuardianReport' => $this->permissions->hasPrivilege('guardian_report', 'can_view'),
            'canStudentHistory' => $this->permissions->hasPrivilege('student_history', 'can_view'),
            'canLoginCredential' => $this->permissions->hasPrivilege('student_login_credential_report', 'can_view'),
            'canClassSubjectReport' => $this->permissions->hasPrivilege('class_subject_report', 'can_view'),
            'canAdmissionReport' => $this->permissions->hasPrivilege('admission_report', 'can_view'),
            'canSiblingReport' => $this->permissions->hasPrivilege('sibling_report', 'can_view'),
            'canStudentProfile' => $this->permissions->hasPrivilege('student_profile', 'can_view'),
            'canGenderRatio' => $this->permissions->hasPrivilege('student_gender_ratio_report', 'can_view'),
            'canTeacherRatio' => $this->permissions->hasPrivilege('student_teacher_ratio_report', 'can_view'),
            'canOnlineAdmissionReport' => $this->permissions->hasPrivilege('online_admission_report', 'can_view'),
            'canAlumniReport' => $this->permissions->hasPrivilege('alumni_report', 'can_view'),
        ];
    }
}
