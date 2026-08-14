<?php

namespace App\Modules\FrontOffice\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintTypeController extends FrontOfficeSetupController
{
    public function editcomplainttype(Request $request, int $id): View|RedirectResponse
    {
        return $this->edit($request, $id);
    }

    protected function master(): array
    {
        return [
            'table' => 'complaint_type',
            'nameField' => 'complaint_type',
            'requiredMessage' => 'The Complaint Type field is required.',
            'indexUrl' => 'admin/complainttype',
            'editUrlPrefix' => 'admin/complainttype/editcomplainttype',
            'deleteUrlPrefix' => 'admin/complainttype/delete',
            'nav' => 'complaint_type',
            'addTitle' => 'Add Complaint Type',
            'editTitle' => 'Edit Complaint Type',
            'listTitle' => 'Complaint Type List',
            'nameLabel' => 'Complaint Type',
        ];
    }
}
