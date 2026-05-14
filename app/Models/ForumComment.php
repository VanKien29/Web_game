<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumComment extends Model
{
    protected $fillable = [
        'forum_post_id',
        'parent_comment_id',
        'nro_account_id',
        'username',
        'avatar_url',
        'content',
        'status',
        'likes',
    ];

    public function post()
    {
        return $this->belongsTo(ForumPost::class, 'forum_post_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_comment_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('status', 'visible');
    }
}
