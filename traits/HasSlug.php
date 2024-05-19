<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::saving(function ($model) {
            $model->slug = Str::slug($model->title);
        });
    }
    
    /**
     * Set the route key name to the slug.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
