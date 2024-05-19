<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    /**
     * Upload a media file and associate it with the model.
     *
     * @param  mixed $file
     * @param  string $collection
     * @return void
     */
    public function addMedia($file, $collection = 'default')
    {
        $path = $file->store('media/' . $collection, 'public');
        $this->media()->create(['path' => $path, 'collection' => $collection]);
    }

    /**
     * Delete all media files associated with the model.
     *
     * @return void
     */
    public function deleteMedia()
    {
        foreach ($this->media as $media) {
            Storage::disk('public')->delete($media->path);
            $media->delete();
        }
    }

    /**
     * Define the relationship with the media model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media()
    {
        return $this->morphMany(\App\Models\Media::class, 'model');
    }
}
