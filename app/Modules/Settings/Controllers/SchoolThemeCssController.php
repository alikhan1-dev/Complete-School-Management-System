<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Services\SchoolThemeCssService;
use Illuminate\Http\Response;

/**
 * CI Theme::css + FrontTheme::css (public text/css).
 */
class SchoolThemeCssController extends Controller
{
    public function __construct(protected SchoolThemeCssService $css)
    {
    }

    /**
     * CI route theme.css → theme/css.
     */
    public function css(): Response
    {
        return response($this->css->backendCss(), 200)
            ->header('Content-Type', 'text/css');
    }

    /**
     * CI route fronttheme.css → FrontTheme/css.
     */
    public function frontCss(): Response
    {
        return response($this->css->frontCss(), 200)
            ->header('Content-Type', 'text/css');
    }
}
