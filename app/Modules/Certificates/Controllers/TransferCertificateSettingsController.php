<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Services\TransferCertificateSettingsService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Transfercertificate index — TC settings only.
 * Deferred: download/print, verify, prepare, custom fields, mPDF.
 */
class TransferCertificateSettingsController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected TransferCertificateSettingsService $settings
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('tc_settings', 'can_view'), 403);

        $row = $this->settings->settings();

        $fields = $this->settings->activeFields();

        return view('shared::layouts.admin', [
            'title' => 'Transfer Certificate Settings',
            'contentView' => 'certificates::admin.transfercertificate.settings',
            'setting' => $row,
            'fields' => $fields,
            'nextTcNo' => $this->settings->nextTcNumber(),
            'assetUrls' => $this->settings->assetUrls($row),
            'canEdit' => $this->permissions->hasPrivilege('tc_settings', 'can_edit')
                || $this->permissions->hasPrivilege('tc_settings', 'can_add'),
            'canDownload' => $this->permissions->hasPrivilege('download_tc', 'can_view'),
            'canVerify' => $this->permissions->hasPrivilege('verify_tc', 'can_view'),
            'canPrepare' => $this->permissions->hasPrivilege('prepare_tc', 'can_view'),
            'fieldLabels' => $fields->mapWithKeys(
                fn ($f) => [$f->id => $this->settings->fieldLabel($f)]
            ),
        ]);
    }

    public function updateHeader(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('tc_settings', 'can_edit')
            || $this->permissions->hasPrivilege('tc_settings', 'can_add'),
            403
        );

        $request->validate([
            'footer_content' => ['nullable', 'string'],
            'header_image' => ['nullable', 'image', 'max:4096'],
            'remove_header_image' => ['nullable'],
        ]);

        $this->settings->updateHeaderFooter($request);

        return redirect()->route('certificates.tc_settings.index')
            ->with('success', 'Header / footer settings saved successfully.')
            ->with('active_tab', 'header');
    }

    public function updateSerial(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('tc_settings', 'can_edit')
            || $this->permissions->hasPrivilege('tc_settings', 'can_add'),
            403
        );

        $request->validate([
            'tc_no_start' => ['required', 'integer', 'min:1'],
            'affiliation_no' => ['nullable', 'string', 'max:255'],
        ]);

        $this->settings->updateSerial($request);

        return redirect()->route('certificates.tc_settings.index')
            ->with('success', 'Serial number / affiliation saved successfully.')
            ->with('active_tab', 'other');
    }

    public function updateImage(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('tc_settings', 'can_edit')
            || $this->permissions->hasPrivilege('tc_settings', 'can_add'),
            403
        );

        $data = $request->validate([
            'field_name' => ['required', 'string', 'in:'.implode(',', TransferCertificateSettingsService::IMAGE_FIELDS)],
            'file' => ['nullable', 'image', 'max:1024'],
            'remove' => ['nullable'],
        ]);

        $this->settings->updateImage(
            $data['field_name'],
            $request->file('file'),
            $request->boolean('remove')
        );

        return redirect()->route('certificates.tc_settings.index')
            ->with('success', 'Signature / image updated successfully.')
            ->with('active_tab', 'other');
    }

    public function updateFields(Request $request): RedirectResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('tc_settings', 'can_edit')
            || $this->permissions->hasPrivilege('tc_settings', 'can_add'),
            403
        );

        $data = $request->validate([
            'fields' => ['required', 'array'],
            'fields.*.id' => ['required', 'integer'],
            'fields.*.position' => ['nullable', 'integer', 'min:1'],
            'fields.*.status' => ['nullable'],
        ]);

        $rows = [];
        foreach ($data['fields'] as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'position' => (int) ($row['position'] ?? 1),
                'status' => ! empty($row['status']) ? 1 : 0,
            ];
        }

        $this->settings->saveFields($rows);

        return redirect()->route('certificates.tc_settings.index')
            ->with('success', 'Transfer certificate fields saved successfully.')
            ->with('active_tab', 'fields');
    }
}
