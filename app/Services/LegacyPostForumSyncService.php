<?php

namespace App\Services;

use App\Models\ForumPost;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LegacyPostForumSyncService
{
    /**
     * @param  Collection<int, Post>  $posts
     * @return array<int, int>
     */
    public function syncMany(Collection $posts): array
    {
        if (! $this->supportsLegacyMapping()) {
            return [];
        }

        $ids = [];
        foreach ($posts as $post) {
            $forumPost = $this->sync($post);
            if ($forumPost) {
                $ids[(int) $post->id] = (int) $forumPost->id;
            }
        }

        return $ids;
    }

    public function sync(Post $post): ?ForumPost
    {
        if (! $this->supportsLegacyMapping()) {
            return null;
        }

        if ($post->status !== 'published') {
            $this->hide($post);

            return null;
        }

        return ForumPost::query()->updateOrCreate(
            ['legacy_post_id' => (int) $post->id],
            $this->payload($post),
        );
    }

    public function hide(Post $post): void
    {
        if (! $this->supportsLegacyMapping()) {
            return;
        }

        ForumPost::query()
            ->where('legacy_post_id', (int) $post->id)
            ->update(['status' => 'deleted']);
    }

    private function payload(Post $post): array
    {
        $publishedAt = $post->published_at ?: $post->created_at ?: now();
        $image = str_replace('\\', '/', trim((string) $post->featured_image));

        return [
            'type' => 'announcement',
            'nro_account_id' => null,
            'author_username' => trim((string) $post->author_username) ?: 'Admin Horizon',
            'author_avatar' => $post->author_avatar ?: '/assets/frontend/home/admin_avatar.jpg',
            'title' => Str::limit(trim((string) $post->title), 160, ''),
            'content' => (string) $post->content,
            'images' => $image !== '' ? [$image] : [],
            'status' => 'published',
            'is_pinned' => false,
            'is_locked' => false,
            'published_at' => $publishedAt,
        ];
    }

    private function supportsLegacyMapping(): bool
    {
        return Schema::hasTable('forum_posts')
            && Schema::hasColumn('forum_posts', 'legacy_post_id');
    }
}
