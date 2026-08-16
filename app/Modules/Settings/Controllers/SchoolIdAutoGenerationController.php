<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Settings\Services\SchoolIdAutoGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CI Schsettings::idautogeneration + saveidautogeneration.
 */
class SchoolIdAutoGenerationController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected SchoolIdAutoGenerationService $idAuto,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_view'), 403);

        $setting = $this->idAuto->current();
        abort_unless($setting !== null, 404);

        return view('shared::layouts.admin', [
            'title' => __('system.id_auto_generation'),
            'contentView' => 'settings::admin.id_auto_generation.index',
            'pageTitle' => __('system.id_auto_generation'),
            'result' => $setting,
            'digitList' => $this->idAuto->digitList(),
            'canEdit' => $this->permissions->hasPrivilege('general_setting', 'can_edit'),
        ]);
    }

    /**
     * CI Schsettings::saveidautogeneration — JSON because CI JS posts this URL.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('general_setting', 'can_edit'), 403);

        $rules = [
            'sch_id' => ['required'],
        ];
        $attributes = [
            'sch_id' => 'Id',
        ];

        if ($request->filled('adm_auto_insert') || $request->boolean('adm_auto_insert')) {
            $rules['adm_prefix'] = ['required'];
            $rules['adm_start_from'] = ['required', 'regex:/^[\-+]?[0-9]+$/'];
            $rules['adm_no_digit'] = ['required', 'regex:/^[\-+]?[0-9]+$/'];
            $attributes['adm_prefix'] = __('system.admission_no_prefix');
            $attributes['adm_start_from'] = __('system.admission_start_from');
            $attributes['adm_no_digit'] = __('system.admission_no_digit');
        }

        if ($request->filled('staffid_auto_insert') || $request->boolean('staffid_auto_insert')) {
            $rules['staffid_prefix'] = ['required'];
            $rules['staffid_start_from'] = ['required', 'regex:/^[\-+]?[0-9]+$/'];
            $rules['staffid_no_digit'] = ['required', 'regex:/^[\-+]?[0-9]+$/'];
            $attributes['staffid_prefix'] = __('system.staff_id_prefix');
            $attributes['staffid_start_from'] = __('system.staff_id_start_from');
            $attributes['staffid_no_digit'] = __('system.staff_id_digit');
        }

        $validator = Validator::make($request->all(), $rules, [], $attributes);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('adm_auto_insert') || $request->boolean('adm_auto_insert')) {
                $digit = $request->input('adm_no_digit');
                $start = (string) $request->input('adm_start_from', '');
                if ($digit !== null && $digit !== '' && strlen($start) !== (int) $digit) {
                    $validator->errors()->add(
                        'adm_no_digit',
                        __('system.admission_start_from').' '.$digit.' '.__('system.digit_long')
                    );
                }
            }

            if ($request->filled('staffid_auto_insert') || $request->boolean('staffid_auto_insert')) {
                $digit = $request->input('staffid_no_digit');
                $start = (string) $request->input('staffid_start_from', '');
                if ($digit !== null && $digit !== '' && strlen($start) !== (int) $digit) {
                    // CI message uses strlen(start), not the digit setting — preserve parity.
                    $validator->errors()->add(
                        'staffid_no_digit',
                        __('system.staff_id_start_from_must_be').' '.strlen($start).' '.__('system.digit_long')
                    );
                }
            }
        });

        if ($validator->fails()) {
            $error = [
                'adm_start_from' => $this->ciErrorHtml($validator, 'adm_start_from'),
                'adm_prefix' => $this->ciErrorHtml($validator, 'adm_prefix'),
                'adm_no_digit' => $this->ciErrorHtml($validator, 'adm_no_digit'),
                'staffid_start_from' => $this->ciErrorHtml($validator, 'staffid_start_from'),
                'staffid_prefix' => $this->ciErrorHtml($validator, 'staffid_prefix'),
                'staffid_no_digit' => $this->ciErrorHtml($validator, 'staffid_no_digit'),
            ];

            if ($this->wantsCiJson($request)) {
                return response()->json(['status' => 'fail', 'error' => $error]);
            }

            return redirect('schsettings/idautogeneration')->withErrors($validator)->withInput();
        }

        $this->idAuto->save([
            'id' => $request->input('sch_id'),
            'adm_start_from' => $request->input('adm_start_from'),
            'adm_prefix' => $request->input('adm_prefix'),
            'adm_no_digit' => $request->input('adm_no_digit'),
            'adm_auto_insert' => $request->has('adm_auto_insert') ? 1 : 0,
            'staffid_start_from' => $request->input('staffid_start_from'),
            'staffid_prefix' => $request->input('staffid_prefix'),
            'staffid_no_digit' => $request->input('staffid_no_digit'),
            'staffid_auto_insert' => $request->has('staffid_auto_insert') ? 1 : 0,
        ]);

        if ($this->wantsCiJson($request)) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => __('system.success_message'),
            ]);
        }

        return redirect('schsettings/idautogeneration')->with('success', __('system.success_message'));
    }

    protected function ciErrorHtml($validator, string $field): string
    {
        return $validator->errors()->has($field)
            ? '<p>'.$validator->errors()->first($field).'</p>'
            : '';
    }

    protected function wantsCiJson(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->wantsJson()
            || str_contains((string) $request->header('Accept', ''), 'application/json');
    }
}
