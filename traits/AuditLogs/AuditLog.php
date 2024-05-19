<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'model_type', 'model_id', 'field', 'old_value', 'new_value', 'user_id'
    ];
}
