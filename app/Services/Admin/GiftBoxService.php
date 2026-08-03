<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gift boxes are item templates only.
 *
 * The game source owns the rewards, chances and options. This service only
 * creates and updates item_template rows with TYPE 27.
 */
class GiftBoxService extends AdminServiceSupport
{
    public function __construct(private readonly GameAssetService $assets)
    {
    }

    public function list(Request $request): array
    {
        if (!$this->itemTemplatesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template.'];
        }

        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $game = DB::connection('game');

        $query = $game->table('item_template as i')
            ->where('i.TYPE', 27)
            ->selectRaw('i.id, i.id as item_id, i.NAME as name, i.NAME as item_name, i.description, i.TYPE as item_type, i.gender, i.icon_id, i.part, i.is_up_to_up, i.can_trade');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('i.id', (int) $search);
                } else {
                    $q->where('i.NAME', 'like', "%{$search}%");
                }
            });
        }

        $total = (clone $query)->count('i.id');
        $boxes = $query->orderByDesc('i.id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($box) {
                $box->id = (int) $box->id;
                $box->item_id = (int) $box->item_id;
                $box->item_type = 27;
                $box->icon_id = (int) ($box->icon_id ?? 0);
                $box->source_managed = true;

                return $box;
            })
            ->values();

        return [
            'ok' => true,
            'data' => $boxes,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function get(int $id): array
    {
        if (!$this->itemTemplatesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template.'];
        }

        $box = DB::connection('game')->table('item_template')
            ->where('id', $id)
            ->where('TYPE', 27)
            ->first();

        if (!$box) {
            return ['ok' => false, 'status' => 404, 'message' => 'Item hộp quà không tồn tại'];
        }

        $box->id = (int) $box->id;
        $box->item_id = (int) $box->id;
        $box->item_type = 27;
        $box->gender = (int) ($box->gender ?? 3);
        $box->icon_id = (int) ($box->icon_id ?? 0);
        $box->part = (int) ($box->part ?? 0);
        $box->is_up_to_up = (bool) ($box->is_up_to_up ?? false);
        $box->can_trade = (bool) ($box->can_trade ?? true);

        return ['ok' => true, 'data' => $box, 'rewards' => []];
    }

    public function create(Request $request): array
    {
        if (!$this->itemTemplatesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template.'];
        }

        $game = DB::connection('game');
        $savedFiles = [];

        if (!$this->hasIcon($request)) {
            return ['ok' => false, 'status' => 422, 'message' => 'Vui lòng upload icon PNG x4 để tạo item template.'];
        }

        try {
            return $game->transaction(function () use ($request, $game, &$savedFiles) {
                $requestedItemId = (int) $request->input('item_id', 0);
                $existing = $requestedItemId > 0
                    ? $game->table('item_template')->where('id', $requestedItemId)->first()
                    : null;

                if ($requestedItemId > 0 && $existing && !$this->isReservedItemTemplate($existing)) {
                    return ['ok' => false, 'status' => 422, 'message' => "item_template ID {$requestedItemId} đã tồn tại"];
                }

                $itemId = $requestedItemId > 0 ? $requestedItemId : $this->nextGameId('item_template');
                $this->ensureItemTemplateContinuity($game, $itemId - 1);
                $iconId = $this->resolveIconId($request, $savedFiles);
                $itemRow = $this->itemPayload($request, $itemId, $iconId);

                if ($existing && $this->isReservedItemTemplate($existing)) {
                    $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
                } else {
                    $game->table('item_template')->insert($itemRow);
                }

                $this->logAdminAction(
                    'gift_box.create',
                    'item_template',
                    $itemId,
                    "Tạo item template hộp quà {$request->input('name')} (#{$itemId})",
                    null,
                    $this->itemSnapshot($itemId)
                );

                return [
                    'ok' => true,
                    'message' => 'Đã tạo item template hộp quà. Hãy thêm reward trong src game.',
                    'id' => $itemId,
                    'item_id' => $itemId,
                ];
            });
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($savedFiles);
            return ['ok' => false, 'status' => 500, 'message' => 'Không thể tạo item hộp quà: ' . $e->getMessage()];
        }
    }

    public function update(Request $request, int $id): array
    {
        if (!$this->itemTemplatesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template.'];
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->where('TYPE', 27)->first();
        if (!$item) {
            return ['ok' => false, 'status' => 404, 'message' => 'Item hộp quà không tồn tại'];
        }

        $before = $this->itemSnapshot($id);
        $savedFiles = [];

        try {
            return $game->transaction(function () use ($request, $game, $item, $id, $before, &$savedFiles) {
                $iconId = $this->resolveIconId($request, $savedFiles, (int) ($item->icon_id ?? 0));
                $itemRow = collect($this->itemPayload($request, $id, $iconId))->except('id')->all();
                $game->table('item_template')->where('id', $id)->update($itemRow);

                $this->logAdminAction(
                    'gift_box.update',
                    'item_template',
                    $id,
                    "Cập nhật item template hộp quà #{$id}",
                    $before,
                    $this->itemSnapshot($id)
                );

                return ['ok' => true, 'message' => 'Đã cập nhật item template hộp quà'];
            });
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($savedFiles);
            return ['ok' => false, 'status' => 500, 'message' => 'Không thể cập nhật item hộp quà: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        if (!$this->itemTemplatesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng item_template.'];
        }

        $game = DB::connection('game');
        $item = $game->table('item_template')->where('id', $id)->where('TYPE', 27)->first();
        if (!$item) {
            return ['ok' => false, 'status' => 404, 'message' => 'Item hộp quà không tồn tại'];
        }

        $before = $this->itemSnapshot($id);
        $game->table('item_template')->where('id', $id)->delete();
        $message = 'Đã xóa item template hộp quà';

        $this->logAdminAction('gift_box.delete', 'item_template', $id, "Xóa item hộp quà #{$id}", $before, null);

        return ['ok' => true, 'message' => $message];
    }

    private function hasIcon(Request $request): bool
    {
        return (bool) ($this->assets->requestFiles($request, 'icon_x4')[0] ?? null)
            || (int) $request->input('icon_id', 0) > 0;
    }

    private function resolveIconId(Request $request, array &$savedFiles, int $fallback = 0): int
    {
        $file = $this->assets->requestFiles($request, 'icon_x4')[0] ?? null;
        if (!$file) {
            return max(0, (int) $request->input('icon_id', $fallback));
        }

        $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
        $iconId = $this->assets->resolveGameAssetId($candidate, 'icon', []);
        $savedFiles = array_merge($savedFiles, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$iconId}.png", 96));

        return $iconId;
    }

    private function itemPayload(Request $request, int $itemId, int $iconId): array
    {
        $row = [
            'id' => $itemId,
            'NAME' => (string) $request->input('name', ''),
            'TYPE' => 27,
            'gender' => 3,
            'description' => (string) $request->input('description', ''),
            'level' => 0,
            'icon_id' => $iconId,
            'part' => 0,
            'is_up_to_up' => 0,
            'power_require' => 0,
            'gold' => 0,
            'gem' => 0,
            'head' => -1,
            'body' => -1,
            'leg' => -1,
            'can_trade' => 1,
            'comment' => 'Admin gift box item. Rewards are defined in Source_game/src.',
        ];

        foreach (array_keys($row) as $column) {
            if (!Schema::connection('game')->hasColumn('item_template', $column)) {
                unset($row[$column]);
            }
        }

        return $row;
    }

    private function itemSnapshot(int $id): ?array
    {
        $item = DB::connection('game')->table('item_template')->where('id', $id)->first();
        return $item ? $this->sanitizeLogState((array) $item) : null;
    }

    private function itemTemplatesReady(): bool
    {
        try {
            return Schema::connection('game')->hasTable('item_template');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureItemTemplateContinuity($game, int $targetId): array
    {
        if ($targetId < 0) {
            return [];
        }

        $existing = $game->table('item_template')
            ->whereBetween('id', [0, $targetId])
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
        $set = array_flip($existing);
        $inserted = [];

        for ($id = 0; $id <= $targetId; $id++) {
            if (isset($set[$id])) {
                continue;
            }

            $row = [
                'id' => $id,
                'TYPE' => 0,
                'gender' => 3,
                'NAME' => "__reserved_item_template_{$id}",
                'description' => 'Reserved placeholder created by admin panel',
                'level' => 0,
                'icon_id' => 0,
                'part' => -1,
                'is_up_to_up' => 0,
                'power_require' => 0,
                'gold' => 0,
                'gem' => 0,
                'head' => -1,
                'body' => -1,
                'leg' => -1,
                'is_up_to_up_over_99' => 0,
                'can_trade' => 0,
                'comment' => 'Admin reserved placeholder to keep item_template IDs contiguous',
            ];

            foreach (array_keys($row) as $column) {
                if (!Schema::connection('game')->hasColumn('item_template', $column)) {
                    unset($row[$column]);
                }
            }

            $game->table('item_template')->insert($row);
            $inserted[] = $id;
        }

        return $inserted;
    }

    private function isReservedItemTemplate(object $item): bool
    {
        $name = (string) ($item->NAME ?? $item->name ?? '');
        $comment = (string) ($item->comment ?? '');

        return str_starts_with($name, '__reserved_item_template_')
            || str_contains($comment, 'Admin reserved placeholder');
    }
}
