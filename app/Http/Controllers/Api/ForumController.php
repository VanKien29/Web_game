<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\ForumPostRead;
use App\Models\ForumPostReaction;
use App\Models\ForumPostSave;
use App\Models\Game\Account;
use App\Models\Game\HeadAvatar;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    private const POST_TYPES = ['announcement', 'player_post', 'feedback'];
    private const PLAYER_POST_TYPES = ['player_post', 'feedback'];
    private const REACTION_TYPES = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

    public function index(Request $request): JsonResponse
    {
        $account = $this->optionalAccount($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(20, max(5, (int) $request->query('per_page', 10)));
        $filter = (string) $request->query('filter', 'all');
        $sort = (string) $request->query('sort', $account ? 'unread' : 'latest');
        $search = trim((string) $request->query('search', ''));
        if (!in_array($sort, ['unread', 'latest', 'hot'], true)) {
            $sort = $account ? 'unread' : 'latest';
        }

        $query = ForumPost::query()
            ->published();

        if ($filter === 'announcements') {
            $query->where('type', 'announcement');
        } elseif ($filter === 'players') {
            $query->where('type', 'player_post');
        } elseif ($filter === 'feedback') {
            $query->where('type', 'feedback');
        } elseif ($filter === 'unread') {
            if (!$account) {
                return $this->emptyFeedResponse($page, $perPage, $account);
            }
            $this->applyUnreadFilter($query, $account);
        } elseif ($filter === 'mine') {
            if (!$account) {
                return $this->emptyFeedResponse($page, $perPage, $account);
            }
            $query->where('nro_account_id', $account->id);
        } elseif ($filter === 'saved') {
            if (!$account) {
                return $this->emptyFeedResponse($page, $perPage, $account);
            }
            $savedIds = ForumPostSave::query()
                ->where('nro_account_id', $account->id)
                ->pluck('forum_post_id')
                ->all();
            $query->whereIn('id', $savedIds ?: [0]);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('author_username', 'like', "%{$search}%");
            });
        }

        $this->applyFeedSort($query, $account, $sort);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ok' => true,
            'data' => $this->hydratePosts($paginator->getCollection(), $account),
            'page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'stats' => $this->feedStats($account),
        ]);
    }

    public function show(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $forumPost->increment('views');
        $this->markPostRead($forumPost, $this->optionalAccount($request));
        $forumPost->refresh();

        return response()->json([
            'ok' => true,
            'data' => $this->hydratePosts(collect([$forumPost]), $this->optionalAccount($request))[0] ?? null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $account = $request->get('game_user');
        $validator = Validator::make($request->all(), [
            'type' => ['nullable', 'in:player_post,feedback'],
            'title' => ['nullable', 'string', 'max:160'],
            'content' => ['required', 'string', 'min:1', 'max:6000'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_urls' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $post = ForumPost::query()->create([
            'type' => in_array($request->input('type'), self::PLAYER_POST_TYPES, true)
                ? $request->input('type')
                : 'player_post',
            'nro_account_id' => $account->id,
            'author_username' => (string) $account->username,
            'author_avatar' => $this->avatarUrlForAccount($account),
            'title' => $this->cleanTitle($request->input('title')),
            'content' => $this->cleanContent($request->input('content')),
            'images' => $this->collectImages($request),
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Đã đăng bài lên diễn đàn.',
            'data' => $this->hydratePosts(collect([$post]), $account)[0] ?? null,
        ], 201);
    }

    public function update(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $account = $request->get('game_user');
        if ((int) $forumPost->nro_account_id !== (int) $account->id) {
            return response()->json(['ok' => false, 'message' => 'Bạn không có quyền sửa bài này.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['nullable', 'in:player_post,feedback'],
            'title' => ['nullable', 'string', 'max:160'],
            'content' => ['required', 'string', 'min:1', 'max:6000'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'keep_images' => ['nullable', 'array'],
            'keep_images.*' => ['nullable', 'string', 'max:500'],
            'image_urls' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $images = $this->mergePostImages($forumPost, $request);

        $forumPost->fill([
            'type' => in_array($request->input('type'), self::PLAYER_POST_TYPES, true)
                ? $request->input('type')
                : $forumPost->type,
            'title' => $this->cleanTitle($request->input('title')),
            'content' => $this->cleanContent($request->input('content')),
            'images' => $images,
        ])->save();

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật bài viết.',
            'data' => $this->hydratePosts(collect([$forumPost->fresh()]), $account)[0] ?? null,
        ]);
    }

    public function destroy(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $account = $request->get('game_user');
        if ((int) $forumPost->nro_account_id !== (int) $account->id) {
            return response()->json(['ok' => false, 'message' => 'Bạn không có quyền xóa bài này.'], 403);
        }

        $forumPost->update(['status' => 'deleted']);

        return response()->json(['ok' => true, 'message' => 'Đã xóa bài viết.']);
    }

    public function toggleReaction(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $type = (string) $request->input('type', 'like');
        if (!in_array($type, self::REACTION_TYPES, true)) {
            return response()->json(['ok' => false, 'message' => 'Cảm xúc không hợp lệ.'], 422);
        }

        $account = $request->get('game_user');
        $reaction = ForumPostReaction::query()
            ->where('forum_post_id', $forumPost->id)
            ->where('nro_account_id', $account->id)
            ->first();

        $active = true;
        if ($reaction && $reaction->type === $type) {
            $reaction->delete();
            $type = '';
            $active = false;
        } elseif ($reaction) {
            $reaction->update(['type' => $type]);
        } else {
            ForumPostReaction::query()->create([
                'forum_post_id' => $forumPost->id,
                'nro_account_id' => $account->id,
                'type' => $type,
            ]);
        }

        $this->refreshPostReactionCount($forumPost);

        return response()->json([
            'ok' => true,
            'active' => $active,
            'reaction' => $type,
            'reaction_count' => (int) $forumPost->fresh()->reaction_count,
            'reaction_counts' => $this->reactionCountsForPost($forumPost->id),
        ]);
    }

    public function toggleSave(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $account = $request->get('game_user');
        $save = ForumPostSave::query()
            ->where('forum_post_id', $forumPost->id)
            ->where('nro_account_id', $account->id)
            ->first();

        if ($save) {
            $save->delete();
            return response()->json(['ok' => true, 'saved' => false]);
        }

        ForumPostSave::query()->create([
            'forum_post_id' => $forumPost->id,
            'nro_account_id' => $account->id,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'saved' => true]);
    }

    public function share(int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $forumPost->increment('share_count');

        return response()->json([
            'ok' => true,
            'share_count' => (int) $forumPost->fresh()->share_count,
        ]);
    }

    public function comments(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $account = $this->optionalAccount($request);
        $this->markPostRead($forumPost, $account);

        return response()->json([
            'ok' => true,
            'data' => $this->commentsPayload($forumPost, $account),
        ]);
    }

    public function markRead(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        $this->markPostRead($forumPost, $request->get('game_user'));

        return response()->json([
            'ok' => true,
            'unread' => $this->unreadCount($request->get('game_user')),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $account = $request->get('game_user');
        $postIds = ForumPost::query()
            ->published()
            ->where(function ($query) use ($account) {
                $query->where('type', 'announcement')
                    ->orWhere('nro_account_id', '!=', $account->id)
                    ->orWhereNull('nro_account_id');
            })
            ->whereNotExists(function ($query) use ($account) {
                $query->selectRaw('1')
                    ->from('forum_post_reads')
                    ->whereColumn('forum_post_reads.forum_post_id', 'forum_posts.id')
                    ->where('forum_post_reads.nro_account_id', $account->id);
            })
            ->pluck('id');

        foreach ($postIds as $postId) {
            ForumPostRead::query()->updateOrCreate(
                ['forum_post_id' => $postId, 'nro_account_id' => $account->id],
                ['read_at' => now()]
            );
        }

        return response()->json([
            'ok' => true,
            'marked' => $postIds->count(),
            'unread' => 0,
        ]);
    }

    public function storeComment(Request $request, int $post): JsonResponse
    {
        $forumPost = $this->findPublicPost($post);
        if (!$forumPost) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại.'], 404);
        }

        if ($forumPost->is_locked) {
            return response()->json(['ok' => false, 'message' => 'Bài viết đã khóa bình luận.'], 423);
        }

        $account = $request->get('game_user');
        $content = $this->cleanContent($request->input('content'));
        $parentId = $request->input('parent_comment_id') ? (int) $request->input('parent_comment_id') : null;

        if ($content === '' || mb_strlen($content) > 1000) {
            return response()->json(['ok' => false, 'message' => 'Bình luận phải từ 1 đến 1000 ký tự.'], 422);
        }

        if ($parentId) {
            $parent = ForumComment::query()
                ->visible()
                ->where('forum_post_id', $forumPost->id)
                ->where('id', $parentId)
                ->first();

            if (!$parent) {
                return response()->json(['ok' => false, 'message' => 'Bình luận gốc không tồn tại.'], 404);
            }

            $parentId = $parent->parent_comment_id ?: $parent->id;
        }

        $comment = ForumComment::query()->create([
            'forum_post_id' => $forumPost->id,
            'parent_comment_id' => $parentId,
            'nro_account_id' => $account->id,
            'username' => (string) $account->username,
            'avatar_url' => $this->avatarUrlForAccount($account),
            'content' => $content,
            'status' => 'visible',
        ]);

        $forumPost->increment('comment_count');

        return response()->json([
            'ok' => true,
            'message' => 'Đã gửi bình luận.',
            'data' => $this->commentRow($comment, $account, []),
            'comment_count' => (int) $forumPost->fresh()->comment_count,
        ], 201);
    }

    public function updateComment(Request $request, int $comment): JsonResponse
    {
        $row = ForumComment::query()->visible()->find($comment);
        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }

        $account = $request->get('game_user');
        if ((int) $row->nro_account_id !== (int) $account->id) {
            return response()->json(['ok' => false, 'message' => 'Bạn không có quyền sửa bình luận này.'], 403);
        }

        $content = $this->cleanContent($request->input('content'));
        if ($content === '' || mb_strlen($content) > 1000) {
            return response()->json(['ok' => false, 'message' => 'Bình luận phải từ 1 đến 1000 ký tự.'], 422);
        }

        $row->update(['content' => $content]);

        return response()->json([
            'ok' => true,
            'message' => 'Đã cập nhật bình luận.',
            'data' => $this->commentRow($row->fresh(), $account, []),
        ]);
    }

    public function destroyComment(Request $request, int $comment): JsonResponse
    {
        $row = ForumComment::query()->visible()->find($comment);
        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }

        $account = $request->get('game_user');
        if ((int) $row->nro_account_id !== (int) $account->id) {
            return response()->json(['ok' => false, 'message' => 'Bạn không có quyền xóa bình luận này.'], 403);
        }

        DB::transaction(function () use ($row) {
            $ids = ForumComment::query()
                ->where('forum_post_id', $row->forum_post_id)
                ->where(function ($query) use ($row) {
                    $query->where('id', $row->id)
                        ->orWhere('parent_comment_id', $row->id);
                })
                ->pluck('id')
                ->all();

            ForumComment::query()->whereIn('id', $ids)->update(['status' => 'deleted']);
            $post = ForumPost::query()->find($row->forum_post_id);
            if ($post) {
                $this->refreshPostCommentCount($post);
            }
        });

        return response()->json(['ok' => true, 'message' => 'Đã xóa bình luận.']);
    }

    public function toggleCommentReaction(Request $request, int $comment): JsonResponse
    {
        $row = ForumComment::query()->visible()->find($comment);
        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }

        $account = $request->get('game_user');
        $liked = DB::transaction(function () use ($row, $account) {
            $exists = DB::table('forum_comment_reactions')
                ->where('forum_comment_id', $row->id)
                ->where('nro_account_id', $account->id)
                ->exists();

            if ($exists) {
                DB::table('forum_comment_reactions')
                    ->where('forum_comment_id', $row->id)
                    ->where('nro_account_id', $account->id)
                    ->delete();
                ForumComment::query()->where('id', $row->id)->where('likes', '>', 0)->decrement('likes');
                return false;
            }

            DB::table('forum_comment_reactions')->insert([
                'forum_comment_id' => $row->id,
                'nro_account_id' => $account->id,
                'created_at' => now(),
            ]);
            ForumComment::query()->where('id', $row->id)->increment('likes');
            return true;
        });

        return response()->json([
            'ok' => true,
            'liked' => $liked,
            'likes' => (int) ForumComment::query()->where('id', $row->id)->value('likes'),
        ]);
    }

    private function emptyFeedResponse(int $page, int $perPage, ?Account $account): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [],
            'page' => $page,
            'total_pages' => 1,
            'total' => 0,
            'stats' => $this->feedStats($account),
        ]);
    }

    private function findPublicPost(int $id): ?ForumPost
    {
        return ForumPost::query()->published()->where('id', $id)->first();
    }

    private function hydratePosts(Collection $posts, ?Account $account): array
    {
        $postIds = $posts->pluck('id')->map(fn($id) => (int) $id)->all();
        if (!$postIds) {
            return [];
        }

        $reactionCounts = [];
        DB::table('forum_post_reactions')
            ->select('forum_post_id', 'type', DB::raw('COUNT(*) as total'))
            ->whereIn('forum_post_id', $postIds)
            ->groupBy('forum_post_id', 'type')
            ->get()
            ->each(function ($row) use (&$reactionCounts) {
                $reactionCounts[(int) $row->forum_post_id][(string) $row->type] = (int) $row->total;
            });

        $userReactions = [];
        $savedIds = [];
        $readIds = [];
        if ($account) {
            $userReactions = ForumPostReaction::query()
                ->where('nro_account_id', $account->id)
                ->whereIn('forum_post_id', $postIds)
                ->pluck('type', 'forum_post_id')
                ->all();

            $savedIds = ForumPostSave::query()
                ->where('nro_account_id', $account->id)
                ->whereIn('forum_post_id', $postIds)
                ->pluck('forum_post_id')
                ->map(fn($id) => (int) $id)
                ->all();

            $readIds = ForumPostRead::query()
                ->where('nro_account_id', $account->id)
                ->whereIn('forum_post_id', $postIds)
                ->pluck('forum_post_id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        $savedLookup = array_flip($savedIds);
        $readLookup = array_flip($readIds);

        return $posts->map(function (ForumPost $post) use ($account, $reactionCounts, $userReactions, $savedLookup, $readLookup) {
            $isOwn = $account
                && $post->type !== 'announcement'
                && (int) $post->nro_account_id === (int) $account->id;
            $isUnread = $account && !$isOwn && !isset($readLookup[(int) $post->id]);

            return [
                'id' => (int) $post->id,
                'type' => (string) $post->type,
                'type_label' => $this->typeLabel($post->type),
                'title' => $post->title,
                'content' => (string) $post->content,
                'images' => array_values(array_filter($post->images ?: [])),
                'author_username' => (string) $post->author_username,
                'author_avatar' => $post->author_avatar ?: $this->defaultAvatarUrl(),
                'status' => (string) $post->status,
                'is_pinned' => (bool) $post->is_pinned,
                'is_locked' => (bool) $post->is_locked,
                'views' => (int) $post->views,
                'reaction_count' => (int) $post->reaction_count,
                'reaction_counts' => $reactionCounts[$post->id] ?? [],
                'comment_count' => (int) $post->comment_count,
                'share_count' => (int) $post->share_count,
                'user_reaction' => $account ? ($userReactions[$post->id] ?? null) : null,
                'is_saved' => isset($savedLookup[$post->id]),
                'is_unread' => $isUnread,
                'can_edit' => $account && (int) $post->nro_account_id === (int) $account->id && $post->type !== 'announcement',
                'can_delete' => $account && (int) $post->nro_account_id === (int) $account->id && $post->type !== 'announcement',
                'created_at' => optional($post->created_at)->toISOString(),
                'updated_at' => optional($post->updated_at)->toISOString(),
            ];
        })->values()->all();
    }

    private function commentsPayload(ForumPost $post, ?Account $account): array
    {
        $comments = ForumComment::query()
            ->visible()
            ->where('forum_post_id', $post->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $likedIds = [];
        if ($account && $comments->isNotEmpty()) {
            $likedIds = DB::table('forum_comment_reactions')
                ->where('nro_account_id', $account->id)
                ->whereIn('forum_comment_id', $comments->pluck('id')->all())
                ->pluck('forum_comment_id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        $likedLookup = array_flip($likedIds);
        $rows = $comments
            ->map(fn(ForumComment $comment) => $this->commentRow($comment, $account, $likedLookup))
            ->all();

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

        return array_values($topLevel);
    }

    private function commentRow(ForumComment $comment, ?Account $account, array $likedLookup): array
    {
        return [
            'id' => (int) $comment->id,
            'forum_post_id' => (int) $comment->forum_post_id,
            'parent_comment_id' => $comment->parent_comment_id ? (int) $comment->parent_comment_id : null,
            'username' => (string) $comment->username,
            'avatar_url' => $comment->avatar_url ?: $this->defaultAvatarUrl(),
            'content' => (string) $comment->content,
            'likes' => (int) $comment->likes,
            'liked' => isset($likedLookup[(int) $comment->id]),
            'can_edit' => $account && (int) $comment->nro_account_id === (int) $account->id,
            'can_delete' => $account && (int) $comment->nro_account_id === (int) $account->id,
            'created_at' => optional($comment->created_at)->toISOString(),
            'updated_at' => optional($comment->updated_at)->toISOString(),
            'replies' => [],
        ];
    }

    private function feedStats(?Account $account): array
    {
        $base = ForumPost::query()->published();

        return [
            'all' => (clone $base)->count(),
            'announcements' => (clone $base)->where('type', 'announcement')->count(),
            'players' => (clone $base)->where('type', 'player_post')->count(),
            'feedback' => (clone $base)->where('type', 'feedback')->count(),
            'unread' => $account ? $this->unreadCount($account) : 0,
            'mine' => $account ? (clone $base)->where('nro_account_id', $account->id)->count() : 0,
            'saved' => $account
                ? ForumPostSave::query()->where('nro_account_id', $account->id)->count()
                : 0,
        ];
    }

    private function collectImages(Request $request): array
    {
        return array_values(array_unique(array_merge(
            $this->storeUploadedImages($request),
            $this->cleanImageUrls($request->input('image_urls'))
        )));
    }

    private function mergePostImages(ForumPost $post, Request $request): array
    {
        $existing = array_values(array_filter($post->images ?: []));
        if ($request->has('keep_images')) {
            $keep = array_intersect($existing, $request->input('keep_images') ?: []);
            $existing = array_values($keep);
        }

        return array_values(array_unique(array_merge(
            $existing,
            $this->storeUploadedImages($request),
            $this->cleanImageUrls($request->input('image_urls'))
        )));
    }

    private function storeUploadedImages(Request $request): array
    {
        $files = $request->file('images', []);
        if (!$files) {
            return [];
        }
        if (!is_array($files)) {
            $files = [$files];
        }

        $directory = public_path('assets/forum/uploads');
        File::ensureDirectoryExists($directory);

        $paths = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $name = now()->format('YmdHis') . '-' . Str::random(14) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $name);
            $paths[] = '/assets/forum/uploads/' . $name;
        }

        return $paths;
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
        $content = preg_replace('/\R{4,}/u', "\n\n\n", $content) ?? $content;

        return $content;
    }

    private function refreshPostReactionCount(ForumPost $post): void
    {
        $post->update([
            'reaction_count' => ForumPostReaction::query()->where('forum_post_id', $post->id)->count(),
        ]);
    }

    private function refreshPostCommentCount(ForumPost $post): void
    {
        $post->update([
            'comment_count' => ForumComment::query()
                ->visible()
                ->where('forum_post_id', $post->id)
                ->count(),
        ]);
    }

    private function applyFeedSort($query, ?Account $account, string $sort): void
    {
        $query->orderByDesc('is_pinned');

        if ($sort === 'unread' && $account) {
            $query->orderByRaw(
                'CASE WHEN type != ? AND nro_account_id = ? THEN 0 WHEN EXISTS (
                    SELECT 1 FROM forum_post_reads
                    WHERE forum_post_reads.forum_post_id = forum_posts.id
                    AND forum_post_reads.nro_account_id = ?
                ) THEN 0 ELSE 1 END DESC',
                ['announcement', $account->id, $account->id]
            );
        } elseif ($sort === 'hot') {
            $query->orderByRaw('(reaction_count + comment_count * 2 + share_count) DESC');
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function applyUnreadFilter($query, Account $account): void
    {
        $query->where(function ($ownerQuery) use ($account) {
            $ownerQuery->where('type', 'announcement')
                ->orWhere('nro_account_id', '!=', $account->id)
                ->orWhereNull('nro_account_id');
        })
            ->whereNotExists(function ($subQuery) use ($account) {
                $subQuery->selectRaw('1')
                    ->from('forum_post_reads')
                    ->whereColumn('forum_post_reads.forum_post_id', 'forum_posts.id')
                    ->where('forum_post_reads.nro_account_id', $account->id);
            });
    }

    private function markPostRead(ForumPost $post, ?Account $account): void
    {
        if (!$account) {
            return;
        }

        if ($post->type !== 'announcement' && (int) $post->nro_account_id === (int) $account->id) {
            return;
        }

        ForumPostRead::query()->updateOrCreate(
            ['forum_post_id' => $post->id, 'nro_account_id' => $account->id],
            ['read_at' => now()]
        );
    }

    private function unreadCount(Account $account): int
    {
        return ForumPost::query()
            ->published()
            ->where(function ($ownerQuery) use ($account) {
                $ownerQuery->where('type', 'announcement')
                    ->orWhere('nro_account_id', '!=', $account->id)
                    ->orWhereNull('nro_account_id');
            })
            ->whereNotExists(function ($query) use ($account) {
                $query->selectRaw('1')
                    ->from('forum_post_reads')
                    ->whereColumn('forum_post_reads.forum_post_id', 'forum_posts.id')
                    ->where('forum_post_reads.nro_account_id', $account->id);
            })
            ->count();
    }

    private function reactionCountsForPost(int $postId): array
    {
        return DB::table('forum_post_reactions')
            ->select('type', DB::raw('COUNT(*) as total'))
            ->where('forum_post_id', $postId)
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn($total) => (int) $total)
            ->all();
    }

    private function typeLabel(string $type): string
    {
        return [
            'announcement' => 'Thông báo',
            'player_post' => 'Bài người chơi',
            'feedback' => 'Góp ý',
        ][$type] ?? 'Bài viết';
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

    private function avatarUrlForAccount(Account $account): string
    {
        $account->loadMissing('player');

        return $this->avatarUrlFromHead($account->player?->head);
    }

    private function avatarUrlFromHead($head): string
    {
        if ($head === null || $head === '') {
            return $this->defaultAvatarUrl();
        }

        $headAvatar = HeadAvatar::query()->where('head_id', $head)->first();
        if (!$headAvatar) {
            return $this->defaultAvatarUrl();
        }

        if (!empty($headAvatar->avatar_id)) {
            return '/assets/frontend/home/v1/images/x4/' . $headAvatar->avatar_id . '.png';
        }

        if (!empty($headAvatar->avatar_url)) {
            return (string) $headAvatar->avatar_url;
        }

        return $this->defaultAvatarUrl();
    }

    private function defaultAvatarUrl(): string
    {
        return '/assets/frontend/home/v1/images/bannergame.png';
    }
}
