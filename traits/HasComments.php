<?php

namespace App\Traits;

use App\Models\Comment;

trait HasComments
{
    /**
     * Get all of the model's comments.
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Add a comment to the model.
     *
     * @param  string  $body
     * @param  int|null  $userId
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function addComment($body, $userId = null)
    {
        $comment = new Comment(['body' => $body, 'user_id' => $userId]);
        return $this->comments()->save($comment);
    }
}
