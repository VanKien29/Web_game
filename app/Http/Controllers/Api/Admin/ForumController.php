<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\ForumComment;
use App\Models\ForumPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    private const POST_TYPES = ['announcement', 'player_post', 'feedback'];
    private const STATUSES = ['published', 'pending', 'hidden', 'deleted'];

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(10, (int) $request->query('per_page', 15)));
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        $query = ForumPost::query()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('author_username', 'like', "%{$search}%");
            });
        }

        if (in_array($type, self::POST_TYPES, true)) {
            $query->where('type', $type);
        }

        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ok' => true,
            'data' => $paginator->getCollection()->map(fn(ForumPost $post) => $this->postRow($post))->values(),
            'page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'stats' => $this->stats(),
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

        $admin = Auth::guard('admin')->user();
        $post = ForumPost::query()->create([
            ...$this->payload($request),
            'nro_account_id' => $admin?->id,
            'author_username' => $admin?->username ?: 'admin',
            'author_avatar' => '/assets/frontend/home/admin_avatar.jpg',
            'published_at' => now(),
        ]);

        $this->logAdminAction('create', 'forum_post', $post->id, 'Tạo bài diễn đàn ' . ($post->title ?: '#' . $post->id), null, $post->toArray());

        return response()->json([
            'ok' => true,
            'message' => 'Đã tạo bài diễn đàn.',
            'data' => $this->postRow($post),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::query()->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài diễn đàn không tồn tại.'], 404);
        }

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $before = $post->toArray();
        $post->fill($this->payload($request))->save();

        $this->logAdminAction('update', 'forum_post', $post->id, 'Cập nhật bài diễn đàn ' . ($post->title ?: '#' . $post->id), $before, $post->fresh()->toArray());

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật bài diễn đàn.',
            'data' => $this->postRow($post->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $post = ForumPost::query()->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài diễn đàn không tồn tại.'], 404);
        }

        $before = $post->toArray();
        $post->update(['status' => 'deleted']);

        $this->logAdminAction('delete', 'forum_post', $post->id, 'Xóa bài diễn đàn ' . ($post->title ?: '#' . $post->id), $before, $post->fresh()->toArray());

        return response()->json(['ok' => true, 'message' => 'Đã xóa bài diễn đàn.']);
    }

    public function comments(int $id): JsonResponse
    {
        $post = ForumPost::query()->find($id);
        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài diễn đàn không tồn tại.'], 404);
        }

        $comments = ForumComment::query()
            ->where('forum_post_id', $post->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn(ForumComment $comment) => [
                'id' => (int) $comment->id,
                'parent_comment_id' => $comment->parent_comment_id ? (int) $comment->parent_comment_id : null,
                'username' => (string) $comment->username,
                'content' => (string) $comment->content,
                'status' => (string) $comment->status,
                'likes' => (int) $comment->likes,
                'created_at' => optional($comment->created_at)->toISOString(),
            ]);

        return response()->json([
            'ok' => true,
            'post' => $this->postRow($post),
            'data' => $comments,
        ]);
    }

    public function destroyComment(int $postId, int $commentId): JsonResponse
    {
        $comment = ForumComment::query()
            ->where('forum_post_id', $postId)
            ->where('id', $commentId)
            ->first();

        if (!$comment) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }

        $before = $comment->toArray();
        $commentCount = DB::transaction(function () use ($postId, $commentId) {
            ForumComment::query()
                ->where('forum_post_id', $postId)
                ->where(function ($query) use ($commentId) {
                    $query->where('id', $commentId)
                        ->orWhere('parent_comment_id', $commentId);
                })
                ->update(['status' => 'deleted']);

            $post = ForumPost::query()->find($postId);
            if ($post) {
                $count = ForumComment::query()
                    ->where('forum_post_id', $post->id)
                    ->where('status', 'visible')
                    ->count();
                $post->update(['comment_count' => $count]);

                return $count;
            }

            return 0;
        });

        $this->logAdminAction('delete', 'forum_comment', $commentId, 'Xóa bình luận diễn đàn của ' . $comment->username, $before, null);

        return response()->json([
            'ok' => true,
            'message' => 'Đã xóa bình luận.',
            'comment_count' => (int) $commentCount,
        ]);
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'type' => ['required', 'in:announcement,player_post,feedback'],
            'title' => ['nullable', 'string', 'max:160'],
            'content' => ['required', 'string', 'min:1', 'max:6000'],
            'status' => ['required', 'in:published,pending,hidden,deleted'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
            'image_urls' => ['nullable'],
        ]);
    }

    private function payload(Request $request): array
    {
        $images = $this->cleanImageUrls($request->input('image_urls'));

        return [
            'type' => (string) $request->input('type', 'announcement'),
            'title' => $this->cleanTitle($request->input('title')),
            'content' => $this->cleanContent($request->input('content')),
            'images' => $images,
            'status' => (string) $request->input('status', 'published'),
            'is_pinned' => (bool) $request->boolean('is_pinned'),
            'is_locked' => (bool) $request->boolean('is_locked'),
        ];
    }

    private function postRow(ForumPost $post): array
    {
        return [
            'id' => (int) $post->id,
            'type' => (string) $post->type,
            'type_label' => $this->typeLabel($post->type),
            'title' => $post->title,
            'content' => (string) $post->content,
            'images' => array_values(array_filter($post->images ?: [])),
            'author_username' => (string) $post->author_username,
            'author_avatar' => $post->author_avatar,
            'status' => (string) $post->status,
            'is_pinned' => (bool) $post->is_pinned,
            'is_locked' => (bool) $post->is_locked,
            'views' => (int) $post->views,
            'reaction_count' => (int) $post->reaction_count,
            'comment_count' => (int) $post->comment_count,
            'share_count' => (int) $post->share_count,
            'created_at' => optional($post->created_at)->toISOString(),
            'updated_at' => optional($post->updated_at)->toISOString(),
        ];
    }

    private function stats(): array
    {
        return [
            'all' => ForumPost::query()->count(),
            'published' => ForumPost::query()->where('status', 'published')->count(),
            'announcements' => ForumPost::query()->where('type', 'announcement')->count(),
            'feedback' => ForumPost::query()->where('type', 'feedback')->count(),
            'hidden' => ForumPost::query()->where('status', 'hidden')->count(),
        ];
    }

    private function cleanImageUrls($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }
        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn($url) => trim((string) $url))
            ->filter(fn($url) => $url !== '' && (str_starts_with($url, '/') || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')))
            ->take(8)
            ->values()
            ->all();
    }

    private function cleanTitle($value): ?string
    {
        $title = trim(strip_tags((string) $value));
        return $title !== '' ? Str::limit($title, 160, '') : null;
    }

    private function cleanContent($value): string
    {
        $content = trim((string) $value);
        $content = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#is', '', $content) ?? '';
        $content = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $content) ?? $content;
        $content = preg_replace('/\s(style|class|id)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $content) ?? $content;
        $content = preg_replace('/(href|src)\s*=\s*([\'"])\s*(javascript|data):[^\'"]*\2/i', '$1="#"', $content) ?? $content;
        $content = preg_replace('/href\s*=\s*([\'"])(?!https?:\/\/|\/|#|mailto:)[^\'"]*\1/i', 'href="#"', $content) ?? $content;
        $content = preg_replace('/href\s*=\s*(?![\'"])(?!https?:\/\/|\/|#|mailto:)[^\s>]+/i', 'href="#"', $content) ?? $content;
        $content = strip_tags($content, '<p><br><strong><b><em><i><u><h3><ul><ol><li><blockquote><a>');
        $content = preg_replace('/\R{4,}/u', "\n\n\n", $content) ?? $content;

        return trim($content);
    }

    private function typeLabel(string $type): string
    {
        return [
            'announcement' => 'Thông báo',
            'player_post' => 'Bài người chơi',
            'feedback' => 'Góp ý',
        ][$type] ?? 'Bài viết';
    }

    private function logAdminAction(
        string $action,
        string $targetType,
        int|string|null $targetId,
        ?string $summary,
        ?array $beforeState = null,
        ?array $afterState = null
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
            ]);
        } catch (\Throwable) {
            //
        }
    }
}
