<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Boot the trait and add model event listeners.
     */
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            Log::info('Created: ' . get_class($model) . ' with ID ' . $model->id);
        });

        static::updated(function ($model) {
            Log::info('Updated: ' . get_class($model) . ' with ID ' . $model->id);
        });

        static::deleted(function ($model) {
            Log::info('Deleted: ' . get_class($model) . ' with ID ' . $model->id);
        });
    }
}
