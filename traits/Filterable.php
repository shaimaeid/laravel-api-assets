<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Apply all relevant query filters.
     *
     * @param  Builder $query
     * @param  array $filters
     * @return Builder
     */
    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            $method = 'filter' . ucfirst($key);

            if (method_exists($this, $method)) {
                $this->{$method}($query, $value);
            }
        }

        return $query;
    }
}
