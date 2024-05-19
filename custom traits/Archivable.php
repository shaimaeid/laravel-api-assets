<?php

namespace App\Traits;

trait Archivable
{
    /**
     * Archive the model.
     */
    public function archive()
    {
        $this->is_archived = true;
        $this->save();
    }

    /**
     * Restore the model from archive.
     */
    public function restoreFromArchive()
    {
        $this->is_archived = false;
        $this->save();
    }

    /**
     * Scope a query to only include archived models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope a query to only include non-archived models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }
}
