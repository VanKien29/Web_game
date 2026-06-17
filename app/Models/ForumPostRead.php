<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPostRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['forum_post_id', 'nro_account_id', 'read_at'];

    public function post()
    {
        return $this->belongsTo(ForumPost::class, 'forum_post_id');
    }
}
