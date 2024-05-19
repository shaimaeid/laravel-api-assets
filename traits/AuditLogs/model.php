<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Trackable;

class Post extends Model
{
    use Trackable;

    // Other model properties and methods

    public function auditLogs()
    {
        return $this->morphMany(\App\Models\AuditLog::class, 'model');
    }
}
