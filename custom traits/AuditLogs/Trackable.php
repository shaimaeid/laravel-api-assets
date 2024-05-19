<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Trackable
{
    /**
     * Boot the trait and add model event listeners.
     */
    public static function bootTrackable()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }
}
