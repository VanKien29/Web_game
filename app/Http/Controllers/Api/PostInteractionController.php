<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Account;
use App\Models\Post;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostInteractionController extends Controller
{
    public function engagement(Request $request, string $slug): JsonResponse
    {
        $post = $this->findPost($slug);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $account = $this->optionalAccount($request);

        return response()->json([
            'ok' => true,
            'data' => $this->engagementPayload($post, $account),
        ]);
    }

    public function comments(Request $request, string $slug): JsonResponse
    {
        $post = $this->findPost($slug);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $account = $this->optionalAccount($request);
        $comments = DB::table('comments')
            ->where('post_id', $post->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $likedCommentIds = [];
        if ($account) {
            $likedCommentIds = DB::table('comment_likes')
                ->where('nro_account_id', $account->id)
                ->whereIn('comment_id', $comments->pluck('id')->all())
                ->pluck('comment_id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        $likedLookup = array_flip($likedCommentIds);
        $rows = $comments->map(fn($row) => [
            'id' => (int) $row->id,
            'post_id' => (int) $row->post_id,
            'parent_comment_id' => $row->parent_comment_id ? (int) $row->parent_comment_id : null,
            'username' => (string) $row->username,
            'avatar_url' => $row->avatar_url ?: null,
            'content' => (string) $row->content,
            'likes' => (int) $row->likes,
            'liked' => isset($likedLookup[(int) $row->id]),
            'created_at' => $row->created_at,
            'replies' => [],
        ])->all();

        $topLevel = [];
        $repliesByParent = [];
        foreach ($rows as $row) {
            if ($row['parent_comment_id']) {
                $repliesByParent[$row['parent_comment_id']][] = $row;
                continue;
            }

            $topLevel[$row['id']] = $row;
        }

        foreach ($repliesByParent as $parentId => $replies) {
            if (isset($topLevel[$parentId])) {
                $topLevel[$parentId]['replies'] = $replies;
            }
        }

        return response()->json([
            'ok' => true,
            'data' => array_values($topLevel),
            'engagement' => $this->engagementPayload($post, $account),
        ]);
    }

    public function storeComment(Request $request, string $slug): JsonResponse
    {
        $post = $this->findPost($slug);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $account = $request->get('game_user');
        $content = trim((string) $request->input('content', ''));
        $parentId = $request->input('parent_comment_id');

        if ($content === '' || mb_strlen($content) > 1000) {
            return response()->json(['ok' => false, 'message' => 'Bình luận phải từ 1 đến 1000 ký tự.'], 422);
        }

        $parentId = $parentId ? (int) $parentId : null;
        if ($parentId) {
            $parent = DB::table('comments')
                ->where('id', $parentId)
                ->where('post_id', $post->id)
                ->first();

            if (!$parent) {
                return response()->json(['ok' => false, 'message' => 'Bình luận gốc không tồn tại.'], 404);
            }

            $parentId = $parent->parent_comment_id ? (int) $parent->parent_comment_id : $parentId;
        }

        $id = DB::table('comments')->insertGetId([
            'post_id' => $post->id,
            'parent_comment_id' => $parentId,
            'nro_account_id' => $account->id,
            'username' => (string) $account->username,
            'avatar_url' => null,
            'content' => $content,
            'likes' => 0,
            'created_at' => now(),
        ]);

        $comment = DB::table('comments')->where('id', $id)->first();

        return response()->json([
            'ok' => true,
            'message' => 'Đã đăng bình luận.',
            'data' => [
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->post_id,
                'parent_comment_id' => $comment->parent_comment_id ? (int) $comment->parent_comment_id : null,
                'username' => (string) $comment->username,
                'avatar_url' => $comment->avatar_url ?: null,
                'content' => (string) $comment->content,
                'likes' => (int) $comment->likes,
                'liked' => false,
                'created_at' => $comment->created_at,
                'replies' => [],
            ],
        ]);
    }

    public function togglePostLike(Request $request, string $slug): JsonResponse
    {
        $post = $this->findPost($slug);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $account = $request->get('game_user');

        $liked = DB::transaction(function () use ($post, $account) {
            $exists = DB::table('post_likes')
                ->where('post_id', $post->id)
                ->where('nro_account_id', $account->id)
                ->exists();

            if ($exists) {
                DB::table('post_likes')
                    ->where('post_id', $post->id)
                    ->where('nro_account_id', $account->id)
                    ->delete();
                DB::table('posts')->where('id', $post->id)->where('likes', '>', 0)->decrement('likes');
                return false;
            }

            DB::table('post_likes')->insert([
                'post_id' => $post->id,
                'nro_account_id' => $account->id,
                'created_at' => now(),
            ]);
            DB::table('posts')->where('id', $post->id)->increment('likes');
            return true;
        });

        $post->refresh();

        return response()->json([
            'ok' => true,
            'liked' => $liked,
            'likes' => (int) $post->likes,
        ]);
    }

    public function toggleCommentLike(Request $request, int $comment): JsonResponse
    {
        $row = DB::table('comments')->where('id', $comment)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại'], 404);
        }

        $account = $request->get('game_user');

        $liked = DB::transaction(function () use ($row, $account) {
            $exists = DB::table('comment_likes')
                ->where('comment_id', $row->id)
                ->where('nro_account_id', $account->id)
                ->exists();

            if ($exists) {
                DB::table('comment_likes')
                    ->where('comment_id', $row->id)
                    ->where('nro_account_id', $account->id)
                    ->delete();
                DB::table('comments')->where('id', $row->id)->where('likes', '>', 0)->decrement('likes');
                return false;
            }

            DB::table('comment_likes')->insert([
                'comment_id' => $row->id,
                'nro_account_id' => $account->id,
                'created_at' => now(),
            ]);
            DB::table('comments')->where('id', $row->id)->increment('likes');
            return true;
        });

        $likes = (int) DB::table('comments')->where('id', $row->id)->value('likes');

        return response()->json([
            'ok' => true,
            'liked' => $liked,
            'likes' => $likes,
        ]);
    }

    private function findPost(string $slug): ?Post
    {
        return Post::published()->where('slug', $slug)->first();
    }

    private function engagementPayload(Post $post, ?Account $account): array
    {
        $liked = false;
        if ($account) {
            $liked = DB::table('post_likes')
                ->where('post_id', $post->id)
                ->where('nro_account_id', $account->id)
                ->exists();
        }

        return [
            'likes' => (int) $post->likes,
            'comments' => (int) DB::table('comments')->where('post_id', $post->id)->count(),
            'liked' => $liked,
        ];
    }

    private function optionalAccount(Request $request): ?Account
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $payload = (new JwtService())->decode($token);
        if (!$payload || !isset($payload->sub)) {
            return null;
        }

        return Account::query()->where('id', $payload->sub)->first();
    }
}
