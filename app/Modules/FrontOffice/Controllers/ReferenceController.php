<?php

namespace App\Modules\FrontOffice\Controllers;

class ReferenceController extends FrontOfficeSetupController
{
    protected function master(): array
    {
        return [
            'table' => 'reference',
            'nameField' => 'reference',
            'requiredMessage' => 'The Reference field is required.',
            'indexUrl' => 'admin/reference',
            'editUrlPrefix' => 'admin/reference/edit',
            'deleteUrlPrefix' => 'admin/reference/delete',
            'nav' => 'reference',
            'addTitle' => 'Add Reference',
            'editTitle' => 'Edit Reference',
            'listTitle' => 'Reference List',
            'nameLabel' => 'Reference',
        ];
    }
}
