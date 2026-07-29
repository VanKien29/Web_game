<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Category;
use App\Models\Post;
use App\Services\LegacyPostForumSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function __construct(
        private readonly LegacyPostForumSyncService $forumSync,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(10, (int) $request->query('per_page', 15)));
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $query = Post::query()
            ->with('category:id,name,slug')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('author_username', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['published', 'draft', 'archived'], true)) {
            $query->where('status', $status);
        }

        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $posts = $paginator->getCollection();
        $commentCounts = $this->commentCounts($posts->pluck('id')->all());
        $forumPostIds = $this->forumSync->syncMany($posts);

        $posts->transform(function (Post $post) use ($commentCounts, $forumPostIds) {
            $post->setAttribute('comments_count', (int) ($commentCounts[$post->id] ?? 0));
            $post->setAttribute('forum_post_id', $forumPostIds[(int) $post->id] ?? null);
            return $post;
        });

        return response()->json([
            'ok' => true,
            'data' => $posts->values(),
            'page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->categoryOptions(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $post = Post::query()->with('category:id,name,slug')->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $post->setAttribute('comments_count', (int) DB::table('comments')->where('post_id', $post->id)->count());
        $post->setAttribute('forum_post_id', $this->forumSync->sync($post)?->id);

        return response()->json([
            'ok' => true,
            'data' => $post,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $this->postPayload($request);
        $post = Post::query()->create($data);
        $forumPost = $this->forumSync->sync($post);
        $post->setAttribute('forum_post_id', $forumPost?->id);

        $this->logAdminAction('create', 'post', $post->id, 'Tạo bài viết ' . $post->title, null, $this->logState($post));

        return response()->json([
            'ok' => true,
            'message' => 'Đã tạo bài viết.',
            'id' => $post->id,
            'data' => $post,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::query()->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $validator = $this->validator($request, $post);
        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $before = $this->logState($post);
        $post->fill($this->postPayload($request, $post));
        $post->save();
        $forumPost = $this->forumSync->sync($post);
        $freshPost = $post->fresh('category:id,name,slug');
        $freshPost?->setAttribute('forum_post_id', $forumPost?->id);

        $this->logAdminAction('update', 'post', $post->id, 'Cập nhật bài viết ' . $post->title, $before, $this->logState($post));

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật bài viết.',
            'data' => $freshPost,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $post = Post::query()->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $before = $this->logState($post);
        DB::transaction(function () use ($post) {
            $commentIds = DB::table('comments')->where('post_id', $post->id)->pluck('id')->all();
            if ($commentIds) {
                DB::table('comment_likes')->whereIn('comment_id', $commentIds)->delete();
                DB::table('comments')->whereIn('id', $commentIds)->delete();
            }

            DB::table('post_likes')->where('post_id', $post->id)->delete();
            $post->delete();
        });
        $this->forumSync->hide($post);

        $this->logAdminAction('delete', 'post', $id, 'Xóa bài viết ' . ($before['title'] ?? $id), $before, null);

        return response()->json(['ok' => true, 'message' => 'Đã xóa bài viết.']);
    }

    public function comments(int $id): JsonResponse
    {
        $post = Post::query()->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }
        $forumPost = $this->forumSync->sync($post);

        $comments = DB::table('comments')
            ->where('post_id', $post->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn($comment) => [
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->post_id,
                'parent_comment_id' => $comment->parent_comment_id ? (int) $comment->parent_comment_id : null,
                'nro_account_id' => $comment->nro_account_id ? (int) $comment->nro_account_id : null,
                'username' => (string) $comment->username,
                'avatar_url' => $comment->avatar_url ?: null,
                'content' => (string) $comment->content,
                'likes' => (int) $comment->likes,
                'created_at' => $comment->created_at,
            ]);

        return response()->json([
            'ok' => true,
            'post' => [
                'id' => (int) $post->id,
                'forum_post_id' => $forumPost?->id,
                'title' => $post->title,
                'slug' => $post->slug,
            ],
            'data' => $comments,
        ]);
    }

    public function updateComment(Request $request, int $postId, int $commentId): JsonResponse
    {
        $comment = DB::table('comments')
            ->where('id', $commentId)
            ->where('post_id', $postId)
            ->first();

        if (!$comment) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại'], 404);
        }

        $content = trim((string) $request->input('content', ''));
        if ($content === '' || mb_strlen($content) > 1000) {
            return response()->json(['ok' => false, 'message' => 'Bình luận phải từ 1 đến 1000 ký tự.'], 422);
        }

        DB::table('comments')->where('id', $commentId)->update(['content' => $content]);

        $after = DB::table('comments')->where('id', $commentId)->first();
        $this->logAdminAction(
            'update',
            'comment',
            $commentId,
            'Sửa bình luận của ' . ($comment->username ?? 'người chơi'),
            (array) $comment,
            (array) $after
        );

        return response()->json(['ok' => true, 'message' => 'Đã cập nhật bình luận.', 'data' => $after]);
    }

    public function destroyComment(int $postId, int $commentId): JsonResponse
    {
        $comment = DB::table('comments')
            ->where('id', $commentId)
            ->where('post_id', $postId)
            ->first();

        if (!$comment) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại'], 404);
        }

        $deletedIds = DB::transaction(function () use ($postId, $commentId) {
            $ids = DB::table('comments')
                ->where('post_id', $postId)
                ->where(function ($query) use ($commentId) {
                    $query->where('id', $commentId)->orWhere('parent_comment_id', $commentId);
                })
                ->pluck('id')
                ->all();

            if ($ids) {
                DB::table('comment_likes')->whereIn('comment_id', $ids)->delete();
                DB::table('comments')->whereIn('id', $ids)->delete();
            }

            return $ids;
        });

        $this->logAdminAction(
            'delete',
            'comment',
            $commentId,
            'Xóa bình luận của ' . ($comment->username ?? 'người chơi'),
            (array) $comment,
            null,
            ['deleted_comment_ids' => array_map('intval', $deletedIds)]
        );

        return response()->json(['ok' => true, 'message' => 'Đã xóa bình luận.']);
    }

    private function validator(Request $request, ?Post $post = null)
    {
        return Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'author_username' => ['nullable', 'string', 'max:100'],
            'author_avatar' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:published,draft,archived'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function postPayload(Request $request, ?Post $post = null): array
    {
        $status = (string) $request->input('status', $post?->status ?: 'draft');
        $content = $this->sanitizeContent((string) $request->input('content', $post?->content ?: ''));
        $excerpt = trim((string) $request->input('excerpt', ''));
        $admin = Auth::guard('admin')->user();

        if ($excerpt === '') {
            $excerpt = Str::limit(trim(strip_tags($content)), 220, '');
        }

        $publishedAt = $this->normalizeDateTime($request->input('published_at'));
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = $post?->published_at ?: now();
        }

        if ($status !== 'published' && !$publishedAt) {
            $publishedAt = null;
        }

        return [
            'title' => trim((string) $request->input('title')),
            'slug' => $this->uniqueSlug((string) $request->input('slug', ''), (string) $request->input('title'), $post?->id),
            'content' => $content,
            'excerpt' => $excerpt,
            'featured_image' => trim((string) $request->input('featured_image', '')) ?: null,
            'category_id' => $request->input('category_id') ? (int) $request->input('category_id') : null,
            'nro_account_id' => $post?->nro_account_id ?: $admin?->id,
            'author_username' => trim((string) $request->input('author_username', '')) ?: ($post?->author_username ?: ($admin?->username ?: 'admin')),
            'author_avatar' => trim((string) $request->input('author_avatar', '')) ?: ($post?->author_avatar ?: null),
            'status' => $status,
            'published_at' => $publishedAt,
        ];
    }

    private function uniqueSlug(string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug(trim($slug) !== '' ? $slug : $title);
        if ($base === '') {
            $base = 'bai-viet';
        }

        $candidate = $base;
        $suffix = 2;

        while (Post::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function normalizeDateTime($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sanitizeContent(string $content): string
    {
        $content = preg_replace('#<(script|iframe|object|embed)[^>]*>.*?</\1>#is', '', $content) ?? '';
        $content = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $content) ?? $content;
        $content = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '$1="#"', $content) ?? $content;

        return $content;
    }

    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status'])
            ->map(fn(Category $category) => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'status' => (string) $category->status,
            ])
            ->all();
    }

    private function commentCounts(array $postIds): array
    {
        if (!$postIds) {
            return [];
        }

        return DB::table('comments')
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->whereIn('post_id', $postIds)
            ->groupBy('post_id')
            ->pluck('total', 'post_id')
            ->map(fn($count) => (int) $count)
            ->all();
    }

    private function logState(Post $post): array
    {
        $state = $post->toArray();
        if (isset($state['content']) && is_string($state['content']) && mb_strlen($state['content']) > 2000) {
            $state['content'] = mb_substr($state['content'], 0, 2000) . ' ...';
        }

        return $state;
    }

    private function logAdminAction(
        string $action,
        string $targetType,
        int|string|null $targetId,
        ?string $summary,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?array $meta = null
    ): void {
        try {
            $admin = Auth::guard('admin')->user();
            AdminActionLog::query()->create([
                'admin_user_id' => $admin?->id,
                'admin_username' => $admin?->username ?? 'admin',
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId !== null ? (string) $targetId : null,
                'target_label' => $summary,
                'summary' => $summary,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'meta' => $meta,
            ]);
        } catch (\Throwable) {
            //
        }
    }
}
