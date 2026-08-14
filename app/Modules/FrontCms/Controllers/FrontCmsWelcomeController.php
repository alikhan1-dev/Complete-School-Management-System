<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsPublicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CI Welcome.php public site (admission / examresult / live mail deferred).
 */
class FrontCmsWelcomeController extends Controller
{
    public function __construct(protected FrontCmsPublicService $public)
    {
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
