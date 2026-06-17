<?php

namespace App\Console\Commands;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\ForumPostReaction;
use App\Models\ForumPostSave;
use App\Models\Game\Account;
use App\Models\Game\HeadAvatar;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeedForumDemoCommand extends Command
{
    protected $signature = 'forum:seed-demo
                            {--posts=60 : Số bài viết người chơi/góp ý cần tạo}
                            {--comments=220 : Số bình luận cần tạo}
                            {--fresh : Xóa toàn bộ dữ liệu forum hiện có trước khi seed}';

    protected $description = 'Tạo dữ liệu diễn đàn demo dựa trên account/player trong database game';

    private array $imagePool = [
        '/assets/frontend/home/v1/images/khai-mo-may-chu.jpg',
        '/assets/frontend/home/v1/images/img-tin-tuc.jpg',
        '/assets/frontend/home/v1/images/img-su-kien.jpg',
        '/assets/frontend/home/v1/images/img-huong-dan.jpg',
        '/assets/frontend/teaser/images/ftgame/teaser1.jpg',
        '/assets/frontend/teaser/images/ftgame/teaser2.jpg',
        '/assets/frontend/teaser/images/ftgame/teaser3.jpg',
        '/assets/frontend/teaser/images/ftgame/teaser4.jpg',
    ];

    public function handle(): int
    {
        $posts = max(10, min(300, (int) $this->option('posts')));
        $comments = max(20, min(1200, (int) $this->option('comments')));

        $accounts = $this->playerAccounts();
        if ($accounts->isEmpty()) {
            $this->error('Không tìm thấy account có nhân vật trong database game.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->freshForum();
        }

        $avatars = $this->avatarMap($accounts);
        $createdPosts = collect();

        Model::unguarded(function () use ($accounts, $avatars, $posts, $comments, &$createdPosts) {
            DB::transaction(function () use ($accounts, $avatars, $posts, $comments, &$createdPosts) {
                $createdPosts = $createdPosts
                    ->merge($this->createAnnouncements($accounts, $avatars))
                    ->merge($this->createPlayerPosts($accounts, $avatars, $posts));

                $this->createComments($createdPosts, $accounts, $avatars, $comments);
                $this->createReactions($createdPosts, $accounts);
                $this->createSaves($createdPosts, $accounts);
                $this->syncCounters($createdPosts);
            });
        });

        $this->info("Đã tạo {$createdPosts->count()} bài forum từ {$accounts->count()} account/player.");
        $this->info('Tổng hiện tại: ' . ForumPost::query()->count() . ' bài, ' . ForumComment::query()->count() . ' bình luận.');

        return self::SUCCESS;
    }

    private function playerAccounts(): Collection
    {
        return Account::query()
            ->with('player')
            ->whereHas('player')
            ->where(function ($query) {
                $query->whereNull('ban')->orWhere('ban', 0);
            })
            ->inRandomOrder()
            ->limit(140)
            ->get()
            ->filter(fn(Account $account) => $account->player !== null)
            ->values();
    }

    private function freshForum(): void
    {
        DB::table('forum_comment_reactions')->delete();
        ForumComment::query()->delete();
        ForumPostSave::query()->delete();
        ForumPostReaction::query()->delete();
        ForumPost::query()->delete();
    }

    private function avatarMap(Collection $accounts): array
    {
        $heads = $accounts
            ->map(fn(Account $account) => $account->player?->head)
            ->filter(fn($head) => $head !== null && $head !== '')
            ->unique()
            ->values()
            ->all();

        if (!$heads) {
            return [];
        }

        return HeadAvatar::query()
            ->whereIn('head_id', $heads)
            ->get()
            ->keyBy('head_id')
            ->all();
    }

    private function createAnnouncements(Collection $accounts, array $avatars): Collection
    {
        $admin = $accounts->first();
        $items = [
            [
                'title' => 'Khai mở diễn đàn cộng đồng Horizon',
                'content' => "Diễn đàn đã mở để anh em báo lỗi, chia sẻ kinh nghiệm và góp ý cân bằng game.\n\nCác bài góp ý rõ nội dung, có tên nhân vật và ảnh minh họa sẽ được ưu tiên xử lý.",
                'is_pinned' => true,
            ],
            [
                'title' => 'Quy định đăng bài và bình luận',
                'content' => "Không spam, không xúc phạm người chơi khác, không đăng link lạ.\n\nBài viết vi phạm sẽ bị ẩn hoặc xóa khỏi diễn đàn.",
                'is_pinned' => true,
            ],
            [
                'title' => 'Thu thập góp ý cập nhật tuần này',
                'content' => "Admin đang tổng hợp phản hồi về săn boss, mốc nạp, giftcode và tỉ lệ rơi vật phẩm. Hãy gửi góp ý cụ thể để đội ngũ kiểm tra nhanh hơn.",
                'is_pinned' => false,
            ],
            [
                'title' => 'Hướng dẫn gửi lỗi nhân vật',
                'content' => "Khi báo lỗi, vui lòng ghi tên nhân vật, thời gian gặp lỗi, map đang đứng và thao tác trước khi lỗi xảy ra.",
                'is_pinned' => false,
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($admin, $avatars) {
            return ForumPost::query()->create([
                'type' => 'announcement',
                'nro_account_id' => $admin?->id,
                'author_username' => 'Admin Horizon',
                'author_avatar' => '/assets/frontend/home/admin_avatar.jpg',
                'title' => $item['title'],
                'content' => $item['content'],
                'images' => $index === 0 ? ['/assets/frontend/home/v1/images/khai-mo-may-chu.jpg'] : [],
                'status' => 'published',
                'is_pinned' => $item['is_pinned'],
                'is_locked' => false,
                'created_at' => now()->subHours(20 - $index * 3),
                'updated_at' => now()->subHours(20 - $index * 3),
                'published_at' => now()->subHours(20 - $index * 3),
            ]);
        });
    }

    private function createPlayerPosts(Collection $accounts, array $avatars, int $count): Collection
    {
        $templates = [
            'player_post' => [
                ['title' => 'Chia sẻ cách up sức mạnh buổi tối', 'content' => "Mình đang chơi {player}, sức mạnh khoảng {power}. Anh em có mẹo train ở map nào ổn hơn không?\n\nHiện tại mình thấy đi theo nhóm đỡ tốn tài nguyên hơn."],
                ['title' => 'Ảnh săn boss hôm nay', 'content' => "Tối nay team mình gặp boss khá vui. {player} đứng tank hơi đuối nhưng cuối cùng vẫn qua được.\n\nAi có lịch boss chuẩn hơn thì chia sẻ giúp mình."],
                ['title' => 'Hỏi về nhiệm vụ đang kẹt', 'content' => "Nhân vật {player} đang bị kẹt ở một đoạn nhiệm vụ. Mình thử đổi map và đăng nhập lại nhưng chưa được.\n\nCó ai từng gặp chưa?"],
                ['title' => 'Góc khoe nhân vật', 'content' => "{player} mới lên được mốc sức mạnh {power}. Chưa mạnh lắm nhưng nhìn nhân vật bắt đầu ổn rồi.\n\nAnh em góp ý cách build chỉ số nhé."],
                ['title' => 'Tìm bang chơi lâu dài', 'content' => "Mình online buổi tối, nhân vật {player}. Muốn tìm bang có hoạt động săn boss và hỗ trợ nhiệm vụ cho người mới."],
                ['title' => 'Kinh nghiệm nạp và nhận mốc', 'content' => "Mình vừa test mốc thưởng, phần nhận khá nhanh. Anh em nhớ kiểm tra hành trang trước khi nhận để tránh đầy đồ."],
            ],
            'feedback' => [
                ['title' => 'Góp ý cân bằng boss', 'content' => "Boss ở một số khung giờ hơi đông người tranh. Có thể thêm thông báo trước khi boss xuất hiện hoặc tăng thêm kênh spawn không admin?"],
                ['title' => 'Đề xuất thêm bộ lọc shop', 'content' => "Shop nhiều vật phẩm nên hơi khó tìm. Mình đề xuất thêm lọc theo hành tinh, cấp, loại trang bị và giá."],
                ['title' => 'Báo lỗi hiển thị chỉ số', 'content' => "Nhân vật {player} đôi lúc đổi trang bị xong chỉ số cập nhật chậm. Reload lại thì bình thường."],
                ['title' => 'Góp ý phần giftcode', 'content' => "Nếu giftcode hết lượt, web nên hiện rõ lý do hết hạn hoặc hết số lần nhập để người chơi dễ hiểu hơn."],
                ['title' => 'Đề xuất sự kiện cuối tuần', 'content' => "Admin có thể thêm sự kiện x2 rơi vật phẩm hoặc nhiệm vụ cộng đồng cuối tuần để anh em có mục tiêu chung."],
            ],
        ];

        $created = collect();
        for ($i = 0; $i < $count; $i++) {
            /** @var Account $account */
            $account = $accounts->random();
            $type = $i % 4 === 0 ? 'feedback' : 'player_post';
            $template = collect($templates[$type])->random();
            $createdAt = now()->subMinutes(random_int(20, 60 * 24 * 12));

            $created->push(ForumPost::query()->create([
                'type' => $type,
                'nro_account_id' => $account->id,
                'author_username' => (string) $account->username,
                'author_avatar' => $this->avatarUrl($account, $avatars),
                'title' => $this->renderText($template['title'], $account),
                'content' => $this->renderText($template['content'], $account),
                'images' => $this->randomImages($type),
                'status' => 'published',
                'is_pinned' => false,
                'is_locked' => random_int(1, 100) <= 4,
                'views' => random_int(15, 900),
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addMinutes(random_int(0, 80)),
                'published_at' => $createdAt,
            ]));
        }

        return $created;
    }

    private function createComments(Collection $posts, Collection $accounts, array $avatars, int $count): void
    {
        $comments = [
            'Mình cũng gặp trường hợp tương tự, đăng xuất vào lại thì ổn.',
            'Bài này hữu ích, để tối mình test thử.',
            'Admin nên ghim góp ý này vì nhiều người đang hỏi.',
            'Bạn thử đổi map rồi quay lại NPC xem sao.',
            'Nếu cần đi boss thì tối nay mình online hỗ trợ.',
            'Mình thấy tỉ lệ hiện tại ổn, chỉ cần thông báo rõ hơn.',
            'Có ảnh hoặc thời gian cụ thể thì admin dễ kiểm tra hơn.',
            'Nhân vật mình cũng đang ở mốc này, cần thêm kinh nghiệm.',
            'Góp ý hợp lý, nhất là phần lọc vật phẩm.',
            'Bang mình còn slot, bạn nhắn trong game nhé.',
        ];

        for ($i = 0; $i < $count; $i++) {
            /** @var ForumPost $post */
            $post = $posts->random();
            /** @var Account $account */
            $account = $accounts->random();
            $createdAt = $post->created_at->copy()->addMinutes(random_int(3, 60 * 18));

            $parentId = null;
            if (random_int(1, 100) <= 28) {
                $parentId = ForumComment::query()
                    ->where('forum_post_id', $post->id)
                    ->whereNull('parent_comment_id')
                    ->where('status', 'visible')
                    ->inRandomOrder()
                    ->value('id');
            }

            ForumComment::query()->create([
                'forum_post_id' => $post->id,
                'parent_comment_id' => $parentId,
                'nro_account_id' => $account->id,
                'username' => (string) $account->username,
                'avatar_url' => $this->avatarUrl($account, $avatars),
                'content' => collect($comments)->random(),
                'status' => 'visible',
                'likes' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function createReactions(Collection $posts, Collection $accounts): void
    {
        $reactionTypes = ['like', 'like', 'like', 'love', 'haha', 'wow', 'sad'];

        foreach ($posts as $post) {
            $reactors = $accounts->shuffle()->take(random_int(2, min(28, $accounts->count())));
            foreach ($reactors as $account) {
                ForumPostReaction::query()->updateOrCreate(
                    ['forum_post_id' => $post->id, 'nro_account_id' => $account->id],
                    ['type' => collect($reactionTypes)->random()]
                );
            }

            $comments = ForumComment::query()
                ->where('forum_post_id', $post->id)
                ->where('status', 'visible')
                ->get();

            foreach ($comments as $comment) {
                $likers = $accounts->shuffle()->take(random_int(0, min(12, $accounts->count())));
                foreach ($likers as $account) {
                    DB::table('forum_comment_reactions')->updateOrInsert(
                        ['forum_comment_id' => $comment->id, 'nro_account_id' => $account->id],
                        ['created_at' => now()->subMinutes(random_int(1, 6000))]
                    );
                }
            }
        }
    }

    private function createSaves(Collection $posts, Collection $accounts): void
    {
        foreach ($accounts->shuffle()->take(min(80, $accounts->count())) as $account) {
            foreach ($posts->shuffle()->take(random_int(1, 6)) as $post) {
                ForumPostSave::query()->updateOrCreate(
                    ['forum_post_id' => $post->id, 'nro_account_id' => $account->id],
                    ['created_at' => now()->subMinutes(random_int(1, 9000))]
                );
            }
        }
    }

    private function syncCounters(Collection $posts): void
    {
        foreach ($posts as $post) {
            ForumComment::query()
                ->where('forum_post_id', $post->id)
                ->where('status', 'visible')
                ->get()
                ->each(function (ForumComment $comment) {
                    $comment->update([
                        'likes' => DB::table('forum_comment_reactions')
                            ->where('forum_comment_id', $comment->id)
                            ->count(),
                    ]);
                });

            $post->update([
                'reaction_count' => ForumPostReaction::query()->where('forum_post_id', $post->id)->count(),
                'comment_count' => ForumComment::query()
                    ->where('forum_post_id', $post->id)
                    ->where('status', 'visible')
                    ->count(),
                'share_count' => random_int(0, 40),
            ]);
        }
    }

    private function renderText(string $text, Account $account): string
    {
        $playerName = $account->player?->name ?: $account->username;

        return strtr($text, [
            '{player}' => (string) $playerName,
            '{power}' => $this->formatPower((int) ($account->player?->power ?? 0)),
        ]);
    }

    private function formatPower(int $power): string
    {
        if ($power >= 1_000_000_000) {
            return round($power / 1_000_000_000, 1) . ' tỷ';
        }

        if ($power >= 1_000_000) {
            return round($power / 1_000_000, 1) . ' triệu';
        }

        if ($power >= 1_000) {
            return round($power / 1_000, 1) . ' nghìn';
        }

        return (string) $power;
    }

    private function randomImages(string $type): array
    {
        $chance = $type === 'feedback' ? 25 : 42;
        if (random_int(1, 100) > $chance) {
            return [];
        }

        return collect($this->imagePool)
            ->shuffle()
            ->take(random_int(1, 3))
            ->values()
            ->all();
    }

    private function avatarUrl(Account $account, array $avatars): string
    {
        $head = $account->player?->head;
        if ($head === null || $head === '') {
            return $this->defaultAvatarUrl();
        }

        $avatar = $avatars[$head] ?? null;
        if (!$avatar) {
            return $this->defaultAvatarUrl();
        }

        if (!empty($avatar->avatar_id)) {
            return '/assets/frontend/home/v1/images/x4/' . $avatar->avatar_id . '.png';
        }

        if (!empty($avatar->avatar_url)) {
            return (string) $avatar->avatar_url;
        }

        return $this->defaultAvatarUrl();
    }

    private function defaultAvatarUrl(): string
    {
        return '/assets/frontend/home/v1/images/bannergame.png';
    }
}
