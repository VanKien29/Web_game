<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPost extends Model
{
    protected $fillable = [
        'type',
        'nro_account_id',
        'author_username',
        'author_avatar',
        'title',
        'content',
        'images',
        'status',
        'is_pinned',
        'is_locked',
        'views',
        'reaction_count',
        'comment_count',
        'share_count',
        'published_at',
    ];

    protected $casts = [
        'images' => 'array',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function comments()
    {
        return $this->hasMany(ForumComment::class);
    }

    public function reactions()
    {
        return $this->hasMany(ForumPostReaction::class);
    }

    public function saves()
    {
        return $this->hasMany(ForumPostSave::class);
    }

    public function reads()
    {
        return $this->hasMany(ForumPostRead::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
