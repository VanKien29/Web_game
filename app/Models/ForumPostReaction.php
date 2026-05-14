<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPostReaction extends Model
{
    protected $fillable = ['forum_post_id', 'nro_account_id', 'type'];

    public function post()
    {
        return $this->belongsTo(ForumPost::class, 'forum_post_id');
    }
}
