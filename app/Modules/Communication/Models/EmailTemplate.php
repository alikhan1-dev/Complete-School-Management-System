<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CI table: email_template.
 */
class EmailTemplate extends BaseModel
{
    protected $table = 'email_template';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'message',
        'created_at',
    ];

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailTemplateAttachment::class, 'email_template_id');
    }
}
