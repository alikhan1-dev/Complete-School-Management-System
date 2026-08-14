<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CI table: email_template_attachment.
 */
class EmailTemplateAttachment extends BaseModel
{
    protected $table = 'email_template_attachment';

    public $timestamps = false;

    protected $fillable = [
        'email_template_id',
        'attachment',
        'attachment_name',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
