<?php

namespace App\Modules\FrontOffice\Controllers;

class SourceController extends FrontOfficeSetupController
{
    protected function master(): array
    {
        return [
            'table' => 'source',
            'nameField' => 'source',
            'requiredMessage' => 'The Source field is required.',
            'indexUrl' => 'admin/source',
            'editUrlPrefix' => 'admin/source/edit',
            'deleteUrlPrefix' => 'admin/source/delete',
            'nav' => 'source',
            'addTitle' => 'Add Source',
            'editTitle' => 'Edit Source',
            'listTitle' => 'Source List',
            'nameLabel' => 'Source',
        ];
    }
}
