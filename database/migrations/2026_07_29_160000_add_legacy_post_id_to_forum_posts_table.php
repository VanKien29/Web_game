<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('forum_posts')) {
            return;
        }

        if (! Schema::hasColumn('forum_posts', 'legacy_post_id')) {
            Schema::table('forum_posts', function (Blueprint $table) {
                $table->unsignedInteger('legacy_post_id')->nullable()->after('id')->unique();
            });
        }

        if (! Schema::hasTable('posts')) {
            return;
        }

        DB::table('posts')
            ->where('status', 'published')
            ->orderBy('id')
            ->get()
            ->each(function ($post) {
                $image = str_replace('\\', '/', trim((string) ($post->featured_image ?? '')));
                $publishedAt = $post->published_at ?: $post->created_at ?: now();

                DB::table('forum_posts')->updateOrInsert(
                    ['legacy_post_id' => (int) $post->id],
                    [
                        'type' => 'announcement',
                        'nro_account_id' => null,
                        'author_username' => trim((string) ($post->author_username ?? '')) ?: 'Admin Horizon',
                        'author_avatar' => $post->author_avatar ?: '/assets/frontend/home/admin_avatar.jpg',
                        'title' => Str::limit(trim((string) $post->title), 160, ''),
                        'content' => (string) $post->content,
                        'images' => json_encode($image !== '' ? [$image] : [], JSON_UNESCAPED_UNICODE),
                        'status' => 'published',
                        'is_pinned' => false,
                        'is_locked' => false,
                        'views' => (int) ($post->views ?? 0),
                        'reaction_count' => 0,
                        'comment_count' => 0,
                        'share_count' => 0,
                        'created_at' => $post->created_at ?: $publishedAt,
                        'updated_at' => $post->updated_at ?: now(),
                        'published_at' => $publishedAt,
                    ],
                );
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('forum_posts')) {
            return;
        }

        if (! Schema::hasColumn('forum_posts', 'legacy_post_id')) {
            return;
        }

        DB::table('forum_posts')
            ->whereNotNull('legacy_post_id')
            ->delete();

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropUnique(['legacy_post_id']);
            $table->dropColumn('legacy_post_id');
        });
    }
};
