<?php

namespace App\Traits;

trait Searchable
{
    /**
     * Scope a query to only include models that match a search term.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $term)
    {
        $columns = $this->searchableColumns ?? ['name']; // Default to 'name' if not specified

        foreach ($columns as $column) {
            $query->orWhere($column, 'LIKE', '%' . $term . '%');
        }

        return $query;
    }
}
