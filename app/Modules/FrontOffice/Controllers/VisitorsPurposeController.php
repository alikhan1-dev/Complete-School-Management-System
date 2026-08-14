<?php

namespace App\Modules\FrontOffice\Controllers;

class VisitorsPurposeController extends FrontOfficeSetupController
{
    protected function master(): array
    {
        return [
            'table' => 'visitors_purpose',
            'nameField' => 'visitors_purpose',
            'requiredMessage' => 'The Purpose field is required.',
            'indexUrl' => 'admin/visitorspurpose',
            'editUrlPrefix' => 'admin/visitorspurpose/edit',
            'deleteUrlPrefix' => 'admin/visitorspurpose/delete',
            'nav' => 'purpose',
            'addTitle' => 'Add Purpose',
            'editTitle' => 'Edit Purpose',
            'listTitle' => 'Purpose List',
            'nameLabel' => 'Purpose',
        ];
    }
}
