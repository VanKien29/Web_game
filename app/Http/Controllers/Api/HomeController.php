<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Slide;
use App\Services\LegacyPostForumSyncService;

class HomeController extends Controller
{
    public function __construct(
        private readonly LegacyPostForumSyncService $forumSync,
    ) {}

    public function index()
    {
        $slides = Slide::active()
            ->orderBy('sort_order')
            ->get();

        $tinTuc = $this->forumLinkedPosts('tin-tuc');
        $suKien = $this->forumLinkedPosts('su-kien');
        $huongDan = $this->forumLinkedPosts('huong-dan');

        $keys = [
            'site_name', 'site_description', 'site_keywords',
            'facebook_url', 'facebook_group_url',
            'ios_download_url', 'android_download_url', 'apk_download_url',
            'payment_url',
            'bank_name', 'bank_account', 'bank_owner', 'transfer_prefix',
        ];

        $settings = Setting::query()
            ->whereIn('key_name', $keys)
            ->pluck('value', 'key_name')
            ->all();

        foreach ($keys as $key) {
            $settings[$key] ??= '';
        }

        return response()->json([
            'slides' => $slides,
            'tin_tuc' => $tinTuc,
            'su_kien' => $suKien,
            'huong_dan' => $huongDan,
            'settings' => $settings,
        ]);
    }

    private function forumLinkedPosts(string $categorySlug)
    {
        $posts = Post::published()
            ->whereHas('category', fn($q) => $q->where('slug', $categorySlug))
            ->with('category:id,name')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get([
                'id',
                'title',
                'slug',
                'content',
                'featured_image',
                'category_id',
                'nro_account_id',
                'author_username',
                'author_avatar',
                'status',
                'views',
                'created_at',
                'updated_at',
                'published_at',
            ]);

        $forumPostIds = $this->forumSync->syncMany($posts);

        return $posts->map(fn(Post $post) => [
            'id' => (int) $post->id,
            'forum_post_id' => $forumPostIds[(int) $post->id] ?? null,
            'title' => (string) $post->title,
            'slug' => (string) $post->slug,
            'created_at' => optional($post->created_at)->toISOString(),
        ])->values();
    }
}
