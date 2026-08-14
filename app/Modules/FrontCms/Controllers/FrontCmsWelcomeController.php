<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsPublicService;
use App\Modules\FrontCms\Services\WelcomeExamResultService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CI Welcome.php public site (live mail deferred).
 */
class FrontCmsWelcomeController extends Controller
{
    public function __construct(
        protected FrontCmsPublicService $public,
        protected WelcomeExamResultService $examResults,
        protected SchoolContext $school,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        $layout = $this->public->layoutData();
        $homeSlug = $this->public->homePageSlug($layout['mainMenus']);
        $page = $this->public->findPageBySlug($homeSlug) ?? ['title' => '', 'description' => '', 'sidebar' => 0];

        return view('frontcms::public.home', array_merge($layout, [
            'page' => $page,
            'bannerImages' => $this->public->bannerImages(),
            'activeMenu' => $homeSlug,
            'pageSideBar' => (int) ($layout['setting']->is_active_sidebar ?? 0),
        ]));
    }

    public function page(Request $request, string $slug): View|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        $found = $this->public->findPageBySlug($slug);
        if ($found !== null && ! empty($found['is_homepage'])) {
            return redirect('frontend');
        }

        $page = $found ?? $this->public->findPageBySlug('404-page') ?? [
            'title' => '404 Page',
            'description' => '',
            'sidebar' => 0,
            'category_content' => [],
        ];
        $page = $this->public->withCategoryList($page, true);
        $formName = $found !== null ? $this->public->formNameFromDescription($found['description'] ?? null) : null;

        if ($request->isMethod('post') && $formName !== null) {
            $validated = $this->validateForm($request, $formName);
            if ($formName === 'contact_us') {
                $this->public->persistContact($validated);
            } else {
                $this->public->persistComplain($validated);
            }

            return redirect('page/'.$slug)->with('success', 'Record saved successfully.');
        }

        $layout = $this->public->layoutData($slug);

        return view('frontcms::public.page', array_merge($layout, [
            'page' => $page,
            'pageHtml' => $this->public->descriptionWithoutForm($page['description'] ?? ''),
            'formName' => $formName,
            'pageSideBar' => (int) ($page['sidebar'] ?? 0),
        ]));
    }

    public function read(string $slug): View|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        $page = $this->public->findProgramBySlug($slug);
        if ($page === null) {
            $fallback = $this->public->findPageBySlug('404-page') ?? [
                'title' => '404 Page',
                'description' => '',
                'sidebar' => 0,
            ];
            $layout = $this->public->layoutData('home');

            return view('frontcms::public.page', array_merge($layout, [
                'page' => $fallback,
                'pageHtml' => $this->public->descriptionWithoutForm($fallback['description'] ?? ''),
                'formName' => null,
                'pageSideBar' => (int) ($fallback['sidebar'] ?? 0),
            ]));
        }

        $layout = $this->public->layoutData('home');

        return view('frontcms::public.read', array_merge($layout, [
            'page' => $page,
            'pageSideBar' => (int) ($page['sidebar'] ?? 0),
        ]));
    }

    public function ajaxPaginationData(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        $type = (string) $request->input('page_content_type', '');
        $offset = (int) $request->input('page', 0);
        $items = $this->public->byCategory($type, $offset, FrontCmsPublicService::PER_PAGE, false);

        return view('frontcms::public.ajax_pagination', [
            'categoryItems' => $items,
            'pageContentType' => $type,
        ]);
    }

    public function setSiteCookies(): Response
    {
        return response('')->cookie('sitecookies', '1', 60 * 24 * 30, '/');
    }

    /**
     * CI Welcome::examresult — Front CMS must be on; sch_settings.exam_result gates the form.
     */
    public function examresult(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        $layout = $this->public->layoutData('examresult');
        $page = [
            'title' => 'Student Exam Result',
            'meta_title' => 'student exam result',
            'meta_keyword' => 'student exam result',
            'meta_description' => 'student exam result',
        ];

        $payload = array_merge($layout, [
            'page' => $page,
            'pageSideBar' => 0,
            'is_exam_result' => $this->examResults->isEnabled(),
            'exam_id' => old('exam_id', ''),
            'exam_result' => [],
            'student_details' => [],
            'exam_grade' => $this->examResults->gradeDetails(),
            'marks_division' => $this->examResults->marksDivisions(),
            'show_roll_no' => (bool) $this->school->get('roll_no'),
            'searched' => false,
            'examResultService' => $this->examResults,
        ]);

        if (! $request->isMethod('post')) {
            return view('frontcms::public.examresult', $payload);
        }

        $validated = $request->validate([
            'admission_no' => ['required'],
            'exam_id' => ['required'],
        ], [], [
            'admission_no' => __('system.admission_no'),
            'exam_id' => __('system.exam'),
        ]);

        $admissionNo = (string) $validated['admission_no'];
        $examId = (int) $validated['exam_id'];
        $studentDetails = $this->examResults->studentExams($admissionNo);
        $examResult = $this->examResults->publishedExamResult(
            $this->examResults->studentSessionIdByAdmissionNo($admissionNo),
            $examId
        );

        if ($examResult === []) {
            session()->flash('msg', '<div class="alert alert-danger">'.e(__('system.no_record_found')).'</div>');
        }

        $payload['exam_id'] = (string) $examId;
        $payload['exam_result'] = $examResult;
        $payload['student_details'] = $studentDetails;
        $payload['searched'] = $request->has('search');

        return view('frontcms::public.examresult', $payload);
    }

    /**
     * CI Welcome::getstudentexam — JSON exam list for admission_no (CI JS posts without CSRF).
     */
    public function getstudentexam(Request $request): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        return response()->json($this->examResults->studentExams((string) $request->input('admission_no', '')));
    }

    protected function gate(): ?RedirectResponse
    {
        if (! $this->public->isPublicEnabled()) {
            return redirect('site/userlogin');
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateForm(Request $request, string $formName): array
    {
        $rules = [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'description' => ['nullable'],
        ];
        if ($formName === 'contact_us') {
            $rules['subject'] = ['required'];
        }
        if ($formName === 'complain') {
            $rules['contact_no'] = ['required'];
        }

        return $request->validate($rules, [], [
            'name' => 'Name',
            'email' => 'Email',
            'subject' => 'Subject',
            'contact_no' => 'Contact no',
        ]);
    }
}
