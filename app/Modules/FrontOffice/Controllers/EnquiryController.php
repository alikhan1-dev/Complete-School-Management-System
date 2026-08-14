<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\EnquiryService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Enquiry — admission enquiry persist (JSON where CI JS expects it).
 */
class EnquiryController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected EnquiryService $enquiries,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('admission_enquiry', 'can_view'), 403);

        $selectedClass = (string) $request->input('class', '');
        $sourceSelect = (string) $request->input('source', '');
        $status = $request->isMethod('post')
            ? (string) $request->input('status', '')
            : 'active';

        $fromDate = trim((string) $request->input('from_date', ''));
        $toDate = trim((string) $request->input('to_date', ''));
        $searched = $request->isMethod('post') && $fromDate !== '' && $toDate !== '';

        if ($searched) {
            $list = $this->enquiries->search(
                $selectedClass,
                $sourceSelect,
                $this->enquiries->parseDate($fromDate),
                $this->enquiries->parseDate($toDate),
                $status,
            );
        } else {
            $list = $this->enquiries->listDefault();
            $status = 'active';
        }

        return view('shared::layouts.admin', [
            'title' => 'Admission Enquiry',
            'contentView' => 'frontoffice::admin.enquiry_index',
            'pageTitle' => 'Admission Enquiry',
            'class_list' => $this->enquiries->classes(),
            'selected_class' => $selectedClass,
            'source_select' => $sourceSelect,
            'status' => $status,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'stff_list' => $this->enquiries->staffList(),
            'enquiry_list' => $list,
            'enquiry_status' => EnquiryService::STATUSES,
            'Reference' => $this->enquiries->references(),
            'sourcelist' => $this->enquiries->sources(),
            'today' => $this->enquiries->formatDate(date('Y-m-d')),
            'canAdd' => $this->permissions->hasPrivilege('admission_enquiry', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('admission_enquiry', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('admission_enquiry', 'can_delete'),
            'canFollowView' => $this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_view'),
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('admission_enquiry', 'can_add'), 403);
        $errors = $this->validateEnquiry($request);
        if ($errors !== []) {
            return response()->json(['status' => 'fail', 'error' => $errors, 'message' => '']);
        }

        $this->enquiries->create($request->all(), $this->enquiries->currentStaffId());

        return response()->json([
            'status' => 'success',
            'error' => '',
            'message' => 'Record saved successfully.',
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('admission_enquiry', 'can_delete'), 403);
        if ($id > 0) {
            $this->enquiries->delete($id);

            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => 'Record deleted successfully.',
            ]);
        }

        return response()->json(['status' => 'fail', 'error' => '', 'message' => '']);
    }

    public function follow_up(int $enquiryId, string $status, int $createdBy): View
    {
        abort_unless($this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_view'), 403);
        $enquiry = $this->enquiries->find($enquiryId, $status);
        abort_if($enquiry === null, 404);
        $assigned = ! empty($enquiry['assigned'])
            ? $this->enquiries->staffById((int) $enquiry['assigned'])
            : null;

        return view('frontoffice::admin.follow_up_modal', [
            'id' => $enquiryId,
            'enquiry_data' => $enquiry,
            'assigned_staff' => $assigned ?: [],
            'next_date' => $this->enquiries->nextFollowUp($enquiryId),
            'created_by' => $this->enquiries->staffById($createdBy) ?: [],
            'enquiry_status' => EnquiryService::STATUSES,
            'login_staff_id' => $this->enquiries->currentStaffId(),
            'staff_role' => $this->enquiries->currentStaffRoleId(),
            'superadmin_rest' => $this->school->superadminRestriction(),
            'canFollowAdd' => $this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_add'),
            'enquiries' => $this->enquiries,
            'today' => $this->enquiries->formatDate(date('Y-m-d')),
        ]);
    }

    public function follow_up_insert(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_add'), 403);
        $errors = [];
        if (trim((string) $request->input('response', '')) === '') {
            $errors['response'] = 'The Response field is required.';
        }
        if (trim((string) $request->input('date', '')) === '') {
            $errors['date'] = 'The Follow Up Date field is required.';
        }
        if (trim((string) $request->input('follow_up_date', '')) === '') {
            $errors['follow_up_date'] = 'The Next Follow Up Date field is required.';
        }
        if ($errors !== []) {
            return response()->json(['status' => 'fail', 'error' => $errors, 'message' => '']);
        }

        $this->enquiries->addFollowUp($request->all(), $this->enquiries->currentStaffId());

        return response()->json([
            'status' => 'success',
            'error' => '',
            'message' => 'Record saved successfully.',
        ]);
    }

    public function follow_up_list(int $id): View
    {
        return view('frontoffice::admin.followup_list', [
            'id' => $id,
            'follow_up_list' => $this->enquiries->followUpList($id),
            'canFollowDelete' => $this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_delete'),
            'enquiries' => $this->enquiries,
        ]);
    }

    public function details(int $id, string $status): View
    {
        abort_unless($this->permissions->hasPrivilege('admission_enquiry', 'can_view'), 403);
        $enquiry = $this->enquiries->find($id, $status);
        abort_if($enquiry === null, 404);

        return view('frontoffice::admin.enquiry_edit_modal', [
            'source' => $this->enquiries->sources(),
            'Reference' => $this->enquiries->references(),
            'class_list' => $this->enquiries->classes(),
            'enquiry_data' => $enquiry,
            'stff_list' => $this->enquiries->staffList(),
            'enquiries' => $this->enquiries,
        ]);
    }

    public function editpost(Request $request, int $id): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('admission_enquiry', 'can_edit'), 403);
        $errors = $this->validateEnquiry($request);
        if ($errors !== []) {
            return response()->json(['status' => 'fail', 'error' => $errors, 'message' => '']);
        }

        $this->enquiries->update($id, $request->all());

        return response()->json([
            'status' => 'success',
            'error' => '',
            'message' => 'Record updated successfully.',
        ]);
    }

    public function follow_up_delete(int $followUpId, int $enquiryId): View
    {
        abort_unless($this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_delete'), 403);
        $this->enquiries->deleteFollowUp($followUpId);

        return view('frontoffice::admin.followup_list', [
            'id' => $enquiryId,
            'follow_up_list' => $this->enquiries->followUpList($enquiryId),
            'canFollowDelete' => $this->permissions->hasPrivilege('follow_up_admission_enquiry', 'can_delete'),
            'enquiries' => $this->enquiries,
        ]);
    }

    public function change_status(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $status = (string) $request->input('status');
        if ($id > 0) {
            $this->enquiries->changeStatus($id, $status);

            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => 'Record saved successfully.',
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'error' => '',
            'message' => 'Record updated successfully.',
        ]);
    }

    public function check_number(Request $request): JsonResponse
    {
        $found = $this->enquiries->checkNumber((string) $request->input('phone_number', ''));
        if ($found !== null) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => 'Number is already exists and name is  '.$found['name'],
            ]);
        }

        return response()->json(['status' => 'fail', 'error' => '', 'message' => '']);
    }

    /**
     * @return array<string, string>
     */
    protected function validateEnquiry(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('name', '')) === '') {
            $errors['name'] = 'The Name field is required.';
        }
        if (trim((string) $request->input('contact', '')) === '') {
            $errors['contact'] = 'The Phone field is required.';
        }
        if (trim((string) $request->input('source', '')) === '') {
            $errors['source'] = 'The Source field is required.';
        }
        if (trim((string) $request->input('date', '')) === '') {
            $errors['date'] = 'The Date field is required.';
        }
        if (trim((string) $request->input('follow_up_date', '')) === '') {
            $errors['follow_up_date'] = 'The Next Follow Up Date field is required.';
        }

        return $errors;
    }
}
