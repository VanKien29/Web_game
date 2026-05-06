<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FlyingBoardService extends AdminServiceSupport
{
    private const ITEM_TYPE = 23;

    public function __construct(private readonly GameAssetService $assets)
    {
    }

    public function list(Request $request): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $search = trim((string) $request->query('search', ''));
        $page = max((int) $request->query('page', 1), 1);
        $limit = max(1, min((int) $request->query('per_page', 30), 100));

        $mounts = $this->mountRows();
        $parts = array_keys($mounts);
        $items = $parts
            ? DB::connection('game')->table('item_template')
                ->where('TYPE', self::ITEM_TYPE)
                ->whereIn('part', $parts)
                ->orderBy('id')
                ->get()
                ->groupBy(fn($row) => (int) $row->part)
            : collect();

        $rows = [];
        foreach ($mounts as $mountId => $mount) {
            $item = $items->get($mountId)?->first();
            $row = [
                'id' => $mountId,
                'mount_id' => $mountId,
                'item_id' => $item ? (int) $item->id : null,
                'type' => self::ITEM_TYPE,
                'gender' => $item ? (int) ($item->gender ?? 3) : 3,
                'name' => $item ? (string) ($item->NAME ?? '') : "mount_{$mountId}",
                'description' => $item ? (string) ($item->description ?? '') : '',
                'icon_id' => $item ? (int) ($item->icon_id ?? 0) : 0,
                'part' => $mountId,
                'n_frame' => (int) ($mount['n_frame'] ?? 0),
                'image_0_id' => $mount['image_0_id'] ?? null,
                'image_1_id' => $mount['image_1_id'] ?? null,
                'image_0_name' => "mount_{$mountId}_0",
                'image_1_name' => "mount_{$mountId}_1",
                'icon_url' => $item ? $this->assets->gameIconUrl((int) ($item->icon_id ?? 0)) : null,
                'image_0_url' => $this->mountImageUrl($mountId, 0),
                'image_1_url' => $this->mountImageUrl($mountId, 1),
            ];

            if ($search !== '' && !$this->matchesSearch($row, $search)) {
                continue;
            }

            $rows[] = $row;
        }

        usort($rows, fn($left, $right) => ($right['mount_id'] <=> $left['mount_id']));
        $total = count($rows);
        $rows = array_slice($rows, ($page - 1) * $limit, $limit);

        return [
            'ok' => true,
            'data' => array_values($rows),
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
            'next' => [
                'item_id' => $this->nextGameId('item_template'),
                'mount_id' => $this->nextMountId(),
            ],
        ];
    }

    public function get(int $mountId): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $mounts = $this->mountRows();
        if (!isset($mounts[$mountId])) {
            return ['ok' => false, 'status' => 404, 'message' => 'Ván bay không tồn tại'];
        }

        $item = DB::connection('game')->table('item_template')
            ->where('TYPE', self::ITEM_TYPE)
            ->where('part', $mountId)
            ->orderBy('id')
            ->first();

        return [
            'ok' => true,
            'data' => [
                'id' => $mountId,
                'mount_id' => $mountId,
                'item_id' => $item ? (int) $item->id : null,
                'name' => $item ? (string) ($item->NAME ?? '') : "mount_{$mountId}",
                'description' => $item ? (string) ($item->description ?? '') : '',
                'gender' => $item ? (int) ($item->gender ?? 3) : 3,
                'icon_id' => $item ? (int) ($item->icon_id ?? 0) : 0,
                'part' => $mountId,
                'n_frame' => (int) ($mounts[$mountId]['n_frame'] ?? 0),
                'image_0_name' => "mount_{$mountId}_0",
                'image_1_name' => "mount_{$mountId}_1",
                'icon_url' => $item ? $this->assets->gameIconUrl((int) ($item->icon_id ?? 0)) : null,
                'image_0_url' => $this->mountImageUrl($mountId, 0),
                'image_1_url' => $this->mountImageUrl($mountId, 1),
            ],
        ];
    }

    public function create(Request $request): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $requestedItemId = (int) $request->input('item_id', 0);
        $requestedExistingItem = null;
        if ($requestedItemId > 0) {
            $requestedExistingItem = $game->table('item_template')->where('id', $requestedItemId)->first();
            if ($requestedExistingItem && !$this->isReservedItemTemplate($requestedExistingItem)) {
                return ['ok' => false, 'status' => 422, 'message' => "item_template ID {$requestedItemId} đã tồn tại"];
            }
        }

        $saved = [];
        $usedIconIds = [];
        $idMap = [];

        try {
            $mount0 = $this->firstUploadedImage($request, 'mount_0_x4');
            $mount1 = $this->firstUploadedImage($request, 'mount_1_x4');
            if (!$mount0 || !$mount1) {
                throw new \InvalidArgumentException('Cần upload đủ ảnh mount_0 và mount_1 x4.');
            }

            $mountId = $this->resolveMountId($this->preferredMountId($request, [$mount0['name'], $mount1['name']]));
            $saved = array_merge(
                $saved,
                $this->saveNamedMountImage($mount0, "mount_{$mountId}_0.png"),
                $this->saveNamedMountImage($mount1, "mount_{$mountId}_1.png"),
            );

            $itemIcon = $this->firstUploadedImage($request, 'item_icon_x4');
            if (!$itemIcon) {
                throw new \InvalidArgumentException('Cần upload icon item x4.');
            }
            $candidateIconId = $this->assets->numericIdFromFilename($itemIcon['name']);
            $itemIconId = $this->assets->resolveGameAssetId($candidateIconId, 'icon', $usedIconIds);
            $saved = array_merge($saved, $this->saveIconImage($itemIcon, "{$itemIconId}.png"));
            if ($candidateIconId !== null) {
                $idMap[$candidateIconId] = $itemIconId;
            }
            $usedIconIds[$itemIconId] = true;
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);
            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu ván bay không hợp lệ: ' . $e->getMessage()];
        }

        $itemId = $requestedItemId > 0 ? $requestedItemId : $this->nextGameId('item_template');
        $name = trim((string) $request->input('name'));
        $nFrame = max(1, (int) $request->input('n_frame', 1));
        $imgId = $this->nextImgByNameRowId();
        $imageRows = [
            ['id' => $imgId, 'NAME' => "mount_{$mountId}_0", 'n_frame' => $nFrame],
            ['id' => $imgId + 1, 'NAME' => "mount_{$mountId}_1", 'n_frame' => $nFrame],
        ];
        $itemRow = $this->itemTemplateRow($itemId, $mountId, $itemIconId, $name, $request);

        DB::beginTransaction();
        $game->beginTransaction();
        try {
            if ($requestedItemId > 0) {
                $this->ensureItemTemplateContinuity($game, $requestedItemId - 1);
            }

            foreach ($imageRows as $row) {
                $game->table('img_by_name')->insert($row);
            }

            if ($requestedExistingItem && $this->isReservedItemTemplate($requestedExistingItem)) {
                $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
            } else {
                $game->table('item_template')->insert($itemRow);
            }

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            if ($game->transactionLevel() > 0) {
                $game->rollBack();
            }
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 500, 'message' => 'Không tạo được ván bay: ' . $e->getMessage()];
        }

        $this->logAdminAction('flying_board.create', 'item_template', $itemId, 'Tạo ván bay ' . $name, null, [
            'item' => $itemRow,
            'img_by_name' => $imageRows,
            'icon_id_map' => $idMap,
            'saved_files' => $saved,
        ]);

        return [
            'ok' => true,
            'message' => "Đã tạo ván bay {$name} (#{$itemId}, mount {$mountId})",
            'data' => [
                'item_id' => $itemId,
                'mount_id' => $mountId,
                'item_icon_id' => $itemIconId,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
            ],
        ];
    }

    public function update(Request $request, int $mountId): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $mounts = $this->mountRows();
        if (!isset($mounts[$mountId])) {
            return ['ok' => false, 'status' => 404, 'message' => 'Ván bay không tồn tại'];
        }

        $item = $game->table('item_template')
            ->where('TYPE', self::ITEM_TYPE)
            ->where('part', $mountId)
            ->orderBy('id')
            ->first();

        $saved = [];
        $oldIconIds = $item ? [(int) ($item->icon_id ?? 0)] : [];
        $itemIconId = $item ? (int) ($item->icon_id ?? 0) : 0;
        $idMap = [];

        try {
            foreach ([0, 1] as $suffix) {
                $upload = $this->firstUploadedImage($request, "mount_{$suffix}_x4");
                if ($upload) {
                    $saved = array_merge($saved, $this->saveNamedMountImage($upload, "mount_{$mountId}_{$suffix}.png"));
                }
            }

            $itemIcon = $this->firstUploadedImage($request, 'item_icon_x4');
            if ($itemIcon) {
                $candidateIconId = $this->assets->numericIdFromFilename($itemIcon['name']);
                $itemIconId = $this->assets->resolveGameAssetId($candidateIconId, 'icon', []);
                $saved = array_merge($saved, $this->saveIconImage($itemIcon, "{$itemIconId}.png"));
                if ($candidateIconId !== null) {
                    $idMap[$candidateIconId] = $itemIconId;
                }
            }
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);
            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu ván bay không hợp lệ: ' . $e->getMessage()];
        }

        $name = trim((string) $request->input('name'));
        $nFrame = max(1, (int) $request->input('n_frame', 1));
        $requestedItemId = (int) $request->input('item_id', 0);
        $requestedExistingItem = null;
        if (!$item && $requestedItemId > 0) {
            $requestedExistingItem = $game->table('item_template')->where('id', $requestedItemId)->first();
            if ($requestedExistingItem && !$this->isReservedItemTemplate($requestedExistingItem)) {
                return ['ok' => false, 'status' => 422, 'message' => "item_template ID {$requestedItemId} đã tồn tại"];
            }
        }

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $this->upsertMountRows($game, $mountId, $nFrame);

            $itemRow = null;
            if ($item || $requestedItemId > 0) {
                $itemId = $item ? (int) $item->id : $requestedItemId;
                if (!$item) {
                    $this->ensureItemTemplateContinuity($game, $itemId - 1);
                }
                $itemRow = $this->itemTemplateRow($itemId, $mountId, $itemIconId, $name, $request);
                if ($item) {
                    $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
                } elseif ($requestedExistingItem && $this->isReservedItemTemplate($requestedExistingItem)) {
                    $game->table('item_template')->where('id', $itemId)->update(collect($itemRow)->except('id')->all());
                } else {
                    $game->table('item_template')->insert($itemRow);
                }
            }

            $safeIconIds = $this->assets->filterUnreferencedIconIds($game, $oldIconIds);
            $deletedIconFiles = $this->assets->deleteGameIconFiles($safeIconIds);

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            if ($game->transactionLevel() > 0) {
                $game->rollBack();
            }
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 500, 'message' => 'Không sửa được ván bay: ' . $e->getMessage()];
        }

        $this->logAdminAction('flying_board.update', 'item_template', $item ? (int) $item->id : $mountId, 'Sửa ván bay ' . $name, [
            'item' => $item ? $this->sanitizeLogState((array) $item) : null,
            'mount' => $mounts[$mountId],
        ], [
            'item' => $itemRow,
            'mount_id' => $mountId,
            'n_frame' => $nFrame,
            'icon_id_map' => $idMap,
            'saved_files' => $saved,
            'deleted_icon_files' => $deletedIconFiles,
        ]);

        return [
            'ok' => true,
            'message' => "Đã sửa ván bay {$name} (mount {$mountId})",
            'data' => [
                'mount_id' => $mountId,
                'item_id' => $item ? (int) $item->id : ($requestedItemId > 0 ? $requestedItemId : null),
                'item_icon_id' => $itemIconId,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    public function delete(int $mountId): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $mounts = $this->mountRows();
        if (!isset($mounts[$mountId])) {
            return ['ok' => false, 'status' => 404, 'message' => 'Ván bay không tồn tại'];
        }

        $items = $game->table('item_template')
            ->where('TYPE', self::ITEM_TYPE)
            ->where('part', $mountId)
            ->get();
        $iconIdsToDelete = $items->pluck('icon_id')->map(fn($value) => (int) $value)->all();

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedFiles = [];
        $deletedIconFiles = [];
        try {
            $maxItemIdBeforeDelete = (int) ($game->table('item_template')->max('id') ?? 0);
            $deletedItemIds = $items->pluck('id')->map(fn($value) => (int) $value)->all();
            if ($deletedItemIds) {
                $game->table('item_template')->whereIn('id', $deletedItemIds)->delete();
            }
            $game->table('img_by_name')->whereIn('NAME', ["mount_{$mountId}_0", "mount_{$mountId}_1"])->delete();
            $filledItemIds = $this->ensureItemTemplateContinuity($game, max(array_merge([$maxItemIdBeforeDelete, 0], $deletedItemIds)));

            $safeIconIds = $this->assets->filterUnreferencedIconIds($game, $iconIdsToDelete);
            $deletedIconFiles = $this->assets->deleteGameIconFiles($safeIconIds);
            $deletedFiles = $this->deleteMountImageFiles($mountId);

            $game->commit();
            DB::commit();
        } catch (\Throwable $e) {
            if ($game->transactionLevel() > 0) {
                $game->rollBack();
            }
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return ['ok' => false, 'status' => 500, 'message' => 'Không xóa được ván bay: ' . $e->getMessage()];
        }

        $this->logAdminAction('flying_board.delete', 'item_template', $mountId, "Xóa ván bay mount {$mountId}", [
            'items' => $items->map(fn($item) => $this->sanitizeLogState((array) $item))->all(),
            'mount' => $mounts[$mountId],
        ], [
            'deleted_item_ids' => $deletedItemIds,
            'filled_item_template_ids' => $filledItemIds,
            'deleted_mount_files' => $deletedFiles,
            'deleted_icon_files' => $deletedIconFiles,
        ]);

        return [
            'ok' => true,
            'message' => 'Đã xóa ván bay, item_template, img_by_name và ảnh không còn dùng',
            'data' => [
                'deleted_item_ids' => $deletedItemIds,
                'deleted_mount_id' => $mountId,
                'filled_item_template_ids' => $filledItemIds,
                'deleted_mount_files' => $deletedFiles,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    private function missingTableMessage(): ?string
    {
        foreach (['item_template', 'img_by_name'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return "Chưa có bảng {$table}";
            }
        }

        return null;
    }

    private function mountRows(): array
    {
        $rows = DB::connection('game')->table('img_by_name')
            ->where('NAME', 'LIKE', 'mount\_%')
            ->get(['id', 'NAME', 'n_frame']);

        $mounts = [];
        foreach ($rows as $row) {
            if (!preg_match('/^mount_(\d+)_([01])$/', (string) $row->NAME, $matches)) {
                continue;
            }

            $mountId = (int) $matches[1];
            $suffix = (int) $matches[2];
            $mounts[$mountId] ??= ['n_frame' => (int) $row->n_frame];
            $mounts[$mountId]["image_{$suffix}_id"] = (int) $row->id;
            $mounts[$mountId]['n_frame'] = max((int) $mounts[$mountId]['n_frame'], (int) $row->n_frame);
        }

        return $mounts;
    }

    private function matchesSearch(array $row, string $search): bool
    {
        $needle = mb_strtolower($search);
        $values = [
            $row['mount_id'],
            $row['item_id'],
            $row['icon_id'],
            $row['name'],
            $row['description'],
            $row['image_0_name'],
            $row['image_1_name'],
        ];

        foreach ($values as $value) {
            if ($value !== null && str_contains(mb_strtolower((string) $value), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function preferredMountId(Request $request, array $filenames): ?int
    {
        if ($request->filled('mount_id')) {
            $id = (int) $request->input('mount_id');
            return $id > 0 ? $id : null;
        }

        foreach ($filenames as $filename) {
            if (preg_match('/mount_(\d+)_[01]/i', $filename, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function resolveMountId(?int $candidate): int
    {
        $id = $candidate && $candidate > 0 ? $candidate : $this->nextMountId();
        while (!$this->mountIdAvailable($id)) {
            $id++;
            if ($id > 32767) {
                throw new \RuntimeException('Không còn mount ID trống hợp lệ (1-32767).');
            }
        }

        return $id;
    }

    private function nextMountId(): int
    {
        $max = 0;
        foreach (array_keys($this->mountRows()) as $mountId) {
            $max = max($max, (int) $mountId);
        }
        $itemMax = (int) (DB::connection('game')->table('item_template')
            ->where('TYPE', self::ITEM_TYPE)
            ->where('part', '>', 0)
            ->max('part') ?? 0);
        $fileMax = $this->maxMountFileId();

        return max($max, $itemMax, $fileMax) + 1;
    }

    private function maxMountFileId(): int
    {
        $max = 0;
        foreach ([1, 2, 3, 4] as $zoom) {
            $dir = $this->assets->gameSrcPath("data/img_by_name/x{$zoom}");
            foreach (glob($dir . DIRECTORY_SEPARATOR . 'mount_*_*.png') ?: [] as $file) {
                if (preg_match('/^mount_(\d+)_[01]$/', pathinfo($file, PATHINFO_FILENAME), $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            }
        }

        return $max;
    }

    private function mountIdAvailable(int $id): bool
    {
        if ($id <= 0 || $id > 32767) {
            return false;
        }

        if (isset($this->mountRows()[$id])) {
            return false;
        }

        if (DB::connection('game')->table('item_template')->where('TYPE', self::ITEM_TYPE)->where('part', $id)->exists()) {
            return false;
        }

        foreach ([1, 2, 3, 4] as $zoom) {
            foreach ([0, 1] as $suffix) {
                if (is_file($this->assets->gameSrcPath("data/img_by_name/x{$zoom}/mount_{$id}_{$suffix}.png"))) {
                    return false;
                }
            }
        }

        return true;
    }

    private function firstUploadedImage(Request $request, string $field): ?array
    {
        $payload = $this->assets->decodeUploadedImagePayload((string) $request->input("{$field}_payload", ''));
        if ($payload) {
            return $payload[0];
        }

        $file = $this->assets->requestFiles($request, $field)[0] ?? null;
        if (!$file) {
            return null;
        }

        return [
            'name' => $file->getClientOriginalName(),
            'file' => $file,
        ];
    }

    private function saveNamedMountImage(array $upload, string $filename): array
    {
        if (isset($upload['bytes'])) {
            return $this->assets->saveGamePngPyramidFromBytes($upload['bytes'], $upload['name'], 'data/img_by_name', $filename);
        }

        return $this->assets->saveGamePngPyramid($upload['file'], 'data/img_by_name', $filename);
    }

    private function saveIconImage(array $upload, string $filename): array
    {
        if (isset($upload['bytes'])) {
            return $this->assets->saveGamePngPyramidFromBytes($upload['bytes'], $upload['name'], 'data/icon', $filename, 96);
        }

        return $this->assets->saveGamePngPyramid($upload['file'], 'data/icon', $filename, 96);
    }

    private function mountImageUrl(int $mountId, int $suffix): ?string
    {
        $filename = "mount_{$mountId}_{$suffix}.png";
        if (!is_file($this->assets->gameSrcPath("data/img_by_name/x4/{$filename}"))) {
            return null;
        }

        return "/assets/game-img-by-name/x4/{$filename}";
    }

    private function deleteMountImageFiles(int $mountId): array
    {
        $deleted = [];
        foreach ([1, 2, 3, 4] as $zoom) {
            foreach ([0, 1] as $suffix) {
                $path = $this->assets->gameSrcPath("data/img_by_name/x{$zoom}/mount_{$mountId}_{$suffix}.png");
                if (is_file($path) && @unlink($path)) {
                    $deleted[] = $path;
                }
            }
        }

        return $deleted;
    }

    private function nextImgByNameRowId(): int
    {
        return ((int) (DB::connection('game')->table('img_by_name')->max('id') ?? 0)) + 1;
    }

    private function upsertMountRows($game, int $mountId, int $nFrame): void
    {
        foreach ([0, 1] as $suffix) {
            $name = "mount_{$mountId}_{$suffix}";
            if ($game->table('img_by_name')->where('NAME', $name)->exists()) {
                $game->table('img_by_name')->where('NAME', $name)->update(['n_frame' => $nFrame]);
                continue;
            }

            $game->table('img_by_name')->insert([
                'id' => $this->nextImgByNameRowId(),
                'NAME' => $name,
                'n_frame' => $nFrame,
            ]);
        }
    }

    private function itemTemplateRow(int $itemId, int $mountId, int $itemIconId, string $name, Request $request): array
    {
        return [
            'id' => $itemId,
            'TYPE' => self::ITEM_TYPE,
            'gender' => (int) $request->input('gender', 3),
            'NAME' => $name,
            'description' => (string) $request->input('description', 'Bay không tốn KI'),
            'level' => 0,
            'icon_id' => $itemIconId,
            'part' => $mountId,
            'is_up_to_up' => 0,
            'power_require' => 0,
            'gold' => 0,
            'gem' => 0,
            'head' => -1,
            'body' => -1,
            'leg' => -1,
            'is_up_to_up_over_99' => 0,
            'can_trade' => 1,
            'comment' => "Admin flying board. mount={$mountId};icon={$itemIconId}",
        ];
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
