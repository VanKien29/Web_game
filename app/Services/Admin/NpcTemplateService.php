<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NpcTemplateService extends AdminServiceSupport
{
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

        $query = DB::connection('game')->table('npc_template')
            ->select(['id', DB::raw('NAME as name'), 'head', 'body', 'leg', 'avatar']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $value = (int) $search;
                    $q->where('id', $value)
                        ->orWhere('avatar', $value)
                        ->orWhere('head', $value)
                        ->orWhere('body', $value)
                        ->orWhere('leg', $value);
                }
                $q->orWhere('NAME', 'LIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn($row) => $this->formatNpcRow($row))
            ->values();

        return [
            'ok' => true,
            'data' => $rows,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => (int) max(1, ceil($total / $limit)),
        ];
    }

    public function get(int $id): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $npc = DB::connection('game')->table('npc_template')->where('id', $id)->first();
        if (!$npc) {
            return ['ok' => false, 'status' => 404, 'message' => 'NPC không tồn tại'];
        }

        return [
            'ok' => true,
            'data' => [
                ...$this->formatNpcRow($npc),
                'head_data' => $this->partData((int) ($npc->head ?? -1)),
                'body_data' => $this->partData((int) ($npc->body ?? -1)),
                'leg_data' => $this->partData((int) ($npc->leg ?? -1)),
            ],
        ];
    }

    public function update(Request $request, int $id): array
    {
        if ($message = $this->missingTableMessage()) {
            return ['ok' => false, 'status' => 422, 'message' => $message];
        }

        $game = DB::connection('game');
        $npc = $game->table('npc_template')->where('id', $id)->first();
        if (!$npc) {
            return ['ok' => false, 'status' => 404, 'message' => 'NPC không tồn tại'];
        }

        $saved = [];
        $oldIconIds = $this->iconIdsFromNpc($npc);

        try {
            [
                'saved' => $saved,
                'id_map' => $idMap,
                'used_icon_ids' => $iconIds,
                'sprite_ids' => $spriteUploadedIds,
            ] = $this->storePartIconUploads($request);

            $headRawLayers = $this->assets->parsePartLayers((string) $request->input('head_data'));
            $bodyRawLayers = $this->assets->parsePartLayers((string) $request->input('body_data'));
            $legRawLayers = $this->assets->parsePartLayers((string) $request->input('leg_data'));
            $allLayers = array_merge($headRawLayers, $bodyRawLayers, $legRawLayers);
            $idMap = $this->assets->completePetSpriteIdMap($idMap, $spriteUploadedIds, $allLayers);

            $headLayers = $this->assets->rewritePetPartLayers($headRawLayers, $idMap);
            $bodyLayers = $this->assets->rewritePetPartLayers($bodyRawLayers, $idMap);
            $legLayers = $this->assets->rewritePetPartLayers($legRawLayers, $idMap);

            $avatarId = $this->storeAvatarIcon($request, $idMap, $iconIds, $saved)
                ?? (int) $request->input('avatar', (int) ($npc->avatar ?? 0));
        } catch (\Throwable $e) {
            $this->assets->deleteFiles($saved);

            return ['ok' => false, 'status' => 422, 'message' => 'Dữ liệu NPC không hợp lệ: ' . $e->getMessage()];
        }

        DB::beginTransaction();
        $game->beginTransaction();
        $deletedIconFiles = [];
        try {
            $headId = $this->validPartId((int) $request->input('head', (int) ($npc->head ?? -1)));
            $bodyId = $this->validPartId((int) $request->input('body', (int) ($npc->body ?? -1)));
            $legId = $this->validPartId((int) $request->input('leg', (int) ($npc->leg ?? -1)));

            foreach ([[$headId, 0, $headLayers], [$bodyId, 1, $bodyLayers], [$legId, 2, $legLayers]] as [$partId, $type, $layers]) {
                $game->table('part')->updateOrInsert(
                    ['id' => $partId],
                    ['TYPE' => $type, 'DATA' => json_encode($layers, JSON_UNESCAPED_UNICODE)],
                );
            }

            $name = trim((string) $request->input('name'));
            $row = [
                'NAME' => $name,
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'avatar' => max(0, min(32767, (int) $avatarId)),
            ];
            $game->table('npc_template')->where('id', $id)->update($row);

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

            return ['ok' => false, 'status' => 500, 'message' => 'Không sửa được NPC: ' . $e->getMessage()];
        }

        $this->logAdminAction('npc_template.update', 'npc_template', $id, 'Sửa NPC ' . $name, $this->sanitizeLogState((array) $npc), [
            'npc' => $row,
            'icon_id_map' => $idMap,
            'saved_files' => $saved,
            'deleted_icon_files' => $deletedIconFiles,
        ]);

        return [
            'ok' => true,
            'message' => "Đã sửa NPC {$name} (#{$id})",
            'data' => [
                'id' => $id,
                'avatar' => $row['avatar'],
                'head' => $headId,
                'body' => $bodyId,
                'leg' => $legId,
                'icon_id_map' => $idMap,
                'saved_files' => $saved,
                'deleted_icon_files' => $deletedIconFiles,
            ],
        ];
    }

    private function missingTableMessage(): ?string
    {
        foreach (['npc_template', 'part'] as $table) {
            if (!Schema::connection('game')->hasTable($table)) {
                return "Chưa có bảng {$table}";
            }
        }

        return null;
    }

    private function formatNpcRow(object $row): array
    {
        $avatar = (int) ($row->avatar ?? 0);

        return [
            'id' => (int) $row->id,
            'name' => (string) ($row->name ?? $row->NAME ?? ''),
            'avatar' => $avatar,
            'head' => (int) ($row->head ?? -1),
            'body' => (int) ($row->body ?? -1),
            'leg' => (int) ($row->leg ?? -1),
            'avatar_url' => $this->assets->gameIconUrl($avatar),
        ];
    }

    private function partData(int $partId): string
    {
        if ($partId < 0) {
            return '';
        }

        $part = DB::connection('game')->table('part')->where('id', $partId)->first(['DATA']);

        return $part ? (string) $part->DATA : '';
    }

    private function validPartId(int $partId): int
    {
        if ($partId < 0 || $partId > 32767) {
            throw new \InvalidArgumentException('Part ID phải nằm trong khoảng 0-32767.');
        }

        return $partId;
    }

    private function iconIdsFromNpc(object $npc): array
    {
        $iconIds = [(int) ($npc->avatar ?? 0)];
        $partIds = array_values(array_filter([
            (int) ($npc->head ?? -1),
            (int) ($npc->body ?? -1),
            (int) ($npc->leg ?? -1),
        ], fn($partId) => $partId >= 0));

        if ($partIds) {
            foreach (DB::connection('game')->table('part')->whereIn('id', $partIds)->get(['DATA']) as $partRow) {
                foreach ($this->assets->decodePartData($partRow->DATA ?? '') as $layer) {
                    $iconIds[] = (int) ($layer['icon_id'] ?? 0);
                }
            }
        }

        return array_values(array_unique(array_filter($iconIds, fn($iconId) => $iconId > 0)));
    }

    private function storePartIconUploads(Request $request): array
    {
        $saved = [];
        $idMap = [];
        $iconIds = [];
        $spriteUploadedIds = [];
        $temporarySourceIconIds = $this->assets->temporaryPartSourceIconIds($request);

        foreach ($this->assets->decodeUploadedImagePayload((string) $request->input('icon_x4_payload', '')) as $payloadFile) {
            $candidate = $this->assets->numericIdFromFilename($payloadFile['name']);
            $id = $this->assets->resolveGameAssetId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, 'icon', $iconIds);
            $saved = array_merge($saved, $this->assets->saveGamePngPyramidFromBytes($payloadFile['bytes'], $payloadFile['name'], 'data/icon', "{$id}.png"));
            if ($candidate !== null) {
                $idMap[$candidate] = $id;
            }
            $iconIds[$id] = true;
            $spriteUploadedIds[] = $id;
        }

        foreach ($this->assets->requestFiles($request, 'icon_x4') as $file) {
            $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
            $id = $this->assets->resolveGameAssetId(isset($temporarySourceIconIds[$candidate ?? 0]) ? null : $candidate, 'icon', $iconIds);
            $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$id}.png"));
            if ($candidate !== null) {
                $idMap[$candidate] = $id;
            }
            $iconIds[$id] = true;
            $spriteUploadedIds[] = $id;
        }

        return [
            'saved' => $saved,
            'id_map' => $idMap,
            'used_icon_ids' => $iconIds,
            'sprite_ids' => $spriteUploadedIds,
        ];
    }

    private function storeAvatarIcon(Request $request, array &$idMap, array &$iconIds, array &$saved): ?int
    {
        $file = $this->assets->requestFiles($request, 'avatar_x4')[0] ?? null;
        if (!$file) {
            return null;
        }

        $candidate = $this->assets->numericIdFromFilename($file->getClientOriginalName());
        if ($candidate !== null && isset($idMap[$candidate])) {
            return (int) $idMap[$candidate];
        }

        $avatarId = $this->assets->resolveGameAssetId($candidate, 'icon', $iconIds);
        $saved = array_merge($saved, $this->assets->saveGamePngPyramid($file, 'data/icon', "{$avatarId}.png", 96));
        if ($candidate !== null) {
            $idMap[$candidate] = $avatarId;
        }
        $iconIds[$avatarId] = true;

        return $avatarId;
    }
}
