<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Slide;

class HomeController extends Controller
{
    public function index()
    {
        $slides = Slide::active()
            ->orderBy('sort_order')
            ->get();

        $tinTuc = Post::published()
            ->whereHas('category', fn($q) => $q->where('slug', 'tin-tuc'))
            ->with('category:id,name')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'created_at', 'category_id']);

        $suKien = Post::published()
            ->whereHas('category', fn($q) => $q->where('slug', 'su-kien'))
            ->with('category:id,name')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'created_at', 'category_id']);

        $huongDan = Post::published()
            ->whereHas('category', fn($q) => $q->where('slug', 'huong-dan'))
            ->with('category:id,name')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'created_at', 'category_id']);

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

    public function postDetail(string $slug)
    {
        $post = Post::published()
            ->with('category:id,name')
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $post->increment('views');

        return response()->json(['ok' => true, 'data' => $post]);
    }
}
