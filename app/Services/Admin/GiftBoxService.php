<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GiftBoxService extends AdminServiceSupport
{
    public function __construct(private readonly GameAssetService $assets)
    {
    }

    public function list(Request $request): array
    {
        if (!$this->tablesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng gift_box_configs/gift_box_rewards. Hãy chạy migrate.'];
        }

        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        $query = DB::connection('game')->table('gift_box_configs as b')
            ->leftJoin('item_template as i', 'i.id', '=', 'b.item_id')
            ->selectRaw('b.*, i.NAME as item_name, i.icon_id, i.TYPE as item_type');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('b.item_id', (int) $search)
                        ->orWhere('b.id', (int) $search);
                } else {
                    $q->where('b.name', 'like', "%{$search}%")
                        ->orWhere('i.NAME', 'like', "%{$search}%");
                }
            });
        }

        $total = (clone $query)->count();
        $boxes = $query->orderByDesc('b.id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($box) {
                $box->id = (int) $box->id;
                $box->item_id = (int) $box->item_id;
                $box->icon_id = isset($box->icon_id) ? (int) $box->icon_id : 0;
                $box->item_type = isset($box->item_type) ? (int) $box->item_type : null;
                $box->active = (bool) $box->active;
                $box->min_empty_slots = (int) $box->min_empty_slots;
                $box->reward_count = (int) DB::connection('game')->table('gift_box_rewards')
                    ->where('gift_box_config_id', $box->id)
                    ->count();

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
        if (!$this->tablesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng gift_box_configs/gift_box_rewards. Hãy chạy migrate.'];
        }

        $box = DB::connection('game')->table('gift_box_configs as b')
            ->leftJoin('item_template as i', 'i.id', '=', 'b.item_id')
            ->where('b.id', $id)
            ->selectRaw('b.*, i.NAME as item_name, i.TYPE as item_type, i.gender, i.icon_id, i.part, i.description as item_description, i.is_up_to_up, i.can_trade')
            ->first();

        if (!$box) {
            return ['ok' => false, 'status' => 404, 'message' => 'Hộp quà không tồn tại'];
        }

        $rewards = DB::connection('game')->table('gift_box_rewards as r')
            ->leftJoin('item_template as i', 'i.id', '=', 'r.reward_item_id')
            ->where('r.gift_box_config_id', $id)
            ->orderBy('r.sort_order')
            ->orderBy('r.id')
            ->get(['r.*', 'i.NAME as reward_name', 'i.icon_id'])
            ->map(fn($reward) => $this->formatReward($reward))
            ->values();

        $box->id = (int) $box->id;
        $box->item_id = (int) $box->item_id;
        $box->item_type = isset($box->item_type) ? (int) $box->item_type : 14;
        $box->gender = isset($box->gender) ? (int) $box->gender : 3;
        $box->icon_id = isset($box->icon_id) ? (int) $box->icon_id : 0;
        $box->part = isset($box->part) ? (int) $box->part : 0;
        $box->active = (bool) $box->active;
        $box->is_up_to_up = (bool) $box->is_up_to_up;
        $box->can_trade = (bool) $box->can_trade;
        $box->min_empty_slots = (int) $box->min_empty_slots;

        return ['ok' => true, 'data' => $box, 'rewards' => $rewards];
    }

    public function create(Request $request): array
    {
        if (!$this->tablesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng gift_box_configs/gift_box_rewards. Hãy chạy migrate.'];
        }

        $game = DB::connection('game');
        $savedFiles = [];

        $hasIconUpload = (bool) ($this->assets->requestFiles($request, 'icon_x4')[0] ?? null);
        if (!$hasIconUpload && (int) $request->input('icon_id', 0) <= 0) {
            return ['ok' => false, 'status' => 422, 'message' => 'Vui lòng upload icon PNG x4 để hệ thống tự sinh icon_id.'];
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

                $boxId = $game->table('gift_box_configs')->insertGetId([
                    'item_id' => $itemId,
                    'name' => (string) $request->input('name', ''),
                    'description' => (string) $request->input('description', ''),
                    'active' => $request->boolean('active', true) ? 1 : 0,
                    'min_empty_slots' => max(1, (int) $request->input('min_empty_slots', 1)),
                    'success_message' => (string) $request->input('success_message', 'Bạn mở rương nhận được {item}'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->syncRewards($boxId, $request->input('rewards', []));
                $after = $this->snapshot($boxId);
                $this->logAdminAction('gift_box.create', 'gift_box', $boxId, "Tạo hộp quà {$request->input('name')} (#{$itemId})", null, $after);

                return ['ok' => true, 'message' => 'Đã tạo hộp quà', 'id' => $boxId, 'item_id' => $itemId];
            });
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($savedFiles);
            return ['ok' => false, 'status' => 500, 'message' => 'Không thể tạo hộp quà: ' . $e->getMessage()];
        }
    }

    public function update(Request $request, int $id): array
    {
        if (!$this->tablesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng gift_box_configs/gift_box_rewards. Hãy chạy migrate.'];
        }

        $game = DB::connection('game');
        $box = $game->table('gift_box_configs')->where('id', $id)->first();
        if (!$box) {
            return ['ok' => false, 'status' => 404, 'message' => 'Hộp quà không tồn tại'];
        }

        $before = $this->snapshot($id);
        $savedFiles = [];

        try {
            return $game->transaction(function () use ($request, $game, $box, $id, $before, &$savedFiles) {
                $iconId = $this->resolveIconId($request, $savedFiles, (int) $request->input('icon_id', 0));
                $itemRow = collect($this->itemPayload($request, (int) $box->item_id, $iconId))->except('id')->all();
                $game->table('item_template')->where('id', $box->item_id)->update($itemRow);

                $game->table('gift_box_configs')->where('id', $id)->update([
                    'name' => (string) $request->input('name', ''),
                    'description' => (string) $request->input('description', ''),
                    'active' => $request->boolean('active', true) ? 1 : 0,
                    'min_empty_slots' => max(1, (int) $request->input('min_empty_slots', 1)),
                    'success_message' => (string) $request->input('success_message', 'Bạn mở rương nhận được {item}'),
                    'updated_at' => now(),
                ]);

                $this->syncRewards($id, $request->input('rewards', []));
                $after = $this->snapshot($id);
                $this->logAdminAction('gift_box.update', 'gift_box', $id, "Cập nhật hộp quà {$request->input('name')}", $before, $after);

                return ['ok' => true, 'message' => 'Đã cập nhật hộp quà'];
            });
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($savedFiles);
            return ['ok' => false, 'status' => 500, 'message' => 'Không thể cập nhật hộp quà: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        if (!$this->tablesReady()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Chưa có bảng gift_box_configs/gift_box_rewards. Hãy chạy migrate.'];
        }

        $before = $this->snapshot($id);
        $box = DB::connection('game')->table('gift_box_configs')->where('id', $id)->first();
        if (!$box) {
            return ['ok' => false, 'status' => 404, 'message' => 'Hộp quà không tồn tại'];
        }

        DB::connection('game')->table('gift_box_configs')->where('id', $id)->delete();
        $this->logAdminAction('gift_box.delete', 'gift_box', $id, 'Xóa cấu hình hộp quà #' . $box->item_id, $before, null);

        return ['ok' => true, 'message' => 'Đã xóa cấu hình hộp quà'];
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
            'TYPE' => (int) $request->input('type', 27),
            'gender' => (int) $request->input('gender', 3),
            'description' => (string) $request->input('description', ''),
            'level' => (int) $request->input('level', 0),
            'icon_id' => $iconId,
            'part' => (int) $request->input('part', 0),
            'is_up_to_up' => $request->boolean('is_up_to_up') ? 1 : 0,
            'power_require' => (int) $request->input('power_require', 0),
            'gold' => 0,
            'gem' => 0,
            'head' => -1,
            'body' => -1,
            'leg' => -1,
            'can_trade' => $request->boolean('can_trade', true) ? 1 : 0,
            'comment' => 'Admin gift box item. dynamic_open=1',
        ];

        foreach (array_keys($row) as $column) {
            if (!Schema::connection('game')->hasColumn('item_template', $column)) {
                unset($row[$column]);
            }
        }

        return $row;
    }

    private function syncRewards(int $boxId, mixed $rawRewards): void
    {
        $rewards = is_array($rawRewards) ? $rawRewards : json_decode((string) $rawRewards, true);
        if (!is_array($rewards)) {
            $rewards = [];
        }

        DB::connection('game')->table('gift_box_rewards')->where('gift_box_config_id', $boxId)->delete();
        $now = now();
        $rows = [];
        foreach (array_values($rewards) as $index => $reward) {
            if (!is_array($reward)) {
                continue;
            }

            $rewardItemId = (int) ($reward['reward_item_id'] ?? $reward['temp_id'] ?? 0);
            if ($rewardItemId <= 0) {
                continue;
            }
            if (!DB::connection('game')->table('item_template')->where('id', $rewardItemId)->exists()) {
                continue;
            }

            $qtyMin = max(1, (int) ($reward['quantity_min'] ?? $reward['quantity'] ?? 1));
            $qtyMax = max($qtyMin, (int) ($reward['quantity_max'] ?? $qtyMin));
            $weight = max(1, (int) ($reward['chance_weight'] ?? $reward['chance'] ?? 1));
            $options = $this->normalizeOptions($reward['options'] ?? []);
            $optionGroups = $this->normalizeOptionGroups($reward['option_groups'] ?? []);

            $rows[] = [
                'gift_box_config_id' => $boxId,
                'reward_item_id' => $rewardItemId,
                'quantity_min' => $qtyMin,
                'quantity_max' => $qtyMax,
                'chance_weight' => $weight,
                'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
                'option_groups_json' => json_encode($optionGroups, JSON_UNESCAPED_UNICODE),
                'sort_order' => (int) ($reward['sort_order'] ?? $index),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::connection('game')->table('gift_box_rewards')->insert($rows);
        }
    }

    private function normalizeOptions(mixed $rawOptions): array
    {
        $options = is_array($rawOptions) ? $rawOptions : json_decode((string) $rawOptions, true);
        if (!is_array($options)) {
            return [];
        }

        $normalized = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $optionId = (int) ($option['id'] ?? $option['option_id'] ?? 0);
            if ($optionId < 0) {
                continue;
            }

            $paramMin = (int) ($option['param_min'] ?? $option['param'] ?? 0);
            $paramMax = (int) ($option['param_max'] ?? $paramMin);
            if ($paramMax < $paramMin) {
                [$paramMin, $paramMax] = [$paramMax, $paramMin];
            }

            $normalized[] = [
                'id' => $optionId,
                'param_min' => $paramMin,
                'param_max' => $paramMax,
            ];
        }

        return $normalized;
    }

    private function normalizeOptionGroups(mixed $rawGroups): array
    {
        $groups = is_array($rawGroups) ? $rawGroups : json_decode((string) $rawGroups, true);
        if (!is_array($groups)) {
            return [];
        }

        $normalized = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $entries = is_array($group['entries'] ?? null) ? $group['entries'] : [];
            $normalizedEntries = [];
            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $options = $this->normalizeOptions($entry['options'] ?? []);
                $normalizedEntries[] = [
                    'label' => trim((string) ($entry['label'] ?? '')),
                    'hsd_value' => isset($entry['hsd_value']) ? trim((string) $entry['hsd_value']) : null,
                    'chance_weight' => max(0, (float) ($entry['chance_weight'] ?? $entry['weight'] ?? 1)),
                    'options' => $options,
                ];
            }

            if (!$normalizedEntries) {
                continue;
            }

            $normalized[] = [
                'name' => trim((string) ($group['name'] ?? 'Nhóm option')),
                'kind' => trim((string) ($group['kind'] ?? 'option')),
                'entries' => $normalizedEntries,
            ];
        }

        return $normalized;
    }

    private function formatReward(object $reward): array
    {
        $options = json_decode((string) ($reward->options_json ?? '[]'), true);
        if (!is_array($options)) {
            $options = [];
        }
        $optionGroups = json_decode((string) ($reward->option_groups_json ?? '[]'), true);
        if (!is_array($optionGroups)) {
            $optionGroups = [];
        }

        return [
            'id' => (int) $reward->id,
            'reward_item_id' => (int) $reward->reward_item_id,
            'reward_name' => (string) ($reward->reward_name ?? ''),
            'icon_id' => isset($reward->icon_id) ? (int) $reward->icon_id : 0,
            'quantity_min' => (int) $reward->quantity_min,
            'quantity_max' => (int) $reward->quantity_max,
            'chance_weight' => (int) $reward->chance_weight,
            'options' => $options,
            'option_groups' => $optionGroups,
            'sort_order' => (int) $reward->sort_order,
        ];
    }

    private function snapshot(int $boxId): ?array
    {
        $box = DB::connection('game')->table('gift_box_configs')->where('id', $boxId)->first();
        if (!$box) {
            return null;
        }

        return [
            'box' => $this->sanitizeLogState((array) $box),
            'rewards' => DB::connection('game')->table('gift_box_rewards')->where('gift_box_config_id', $boxId)->orderBy('sort_order')->get()->map(fn($row) => (array) $row)->all(),
        ];
    }

    private function tablesReady(): bool
    {
        try {
            return Schema::connection('game')->hasTable('gift_box_configs')
                && Schema::connection('game')->hasTable('gift_box_rewards')
                && Schema::connection('game')->hasTable('item_template');
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
