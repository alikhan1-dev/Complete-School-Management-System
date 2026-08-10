<?php

namespace App\Modules\Shared\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Thin base for Smart School legacy tables.
 * Keep this class small — no auth, permissions, formatting, or logging.
 */
abstract class BaseModel extends Model
{
    /**
     * Disable Laravel's automatic timestamps by default.
     * Opt in per model when the table has proper datetime created_at/updated_at.
     */
    public $timestamps = false;
}
