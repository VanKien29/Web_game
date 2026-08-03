<?php

namespace App\Services\Admin;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WelfareConfigService extends AdminServiceSupport
{
    public const TYPE_LABELS = [
        'attendance_daily' => 'Điểm danh hằng ngày',
        'attendance_milestone' => 'Mốc điểm danh',
        'level' => 'Mốc cấp độ',
        'online' => 'Mốc online',
        'daily_package' => 'Gói ngày',
        'vip_package' => 'Gói ưu đãi',
        'first_topup' => 'Nạp đầu',
        'message' => 'Nội dung hệ thống',
    ];

    public function __construct(private readonly GameCatalogService $catalog) {}

    public function list(Request $request): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }

        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $active = $request->query('active');
        $page = max((int) $request->query('page', 1), 1);
        $limit = min(max((int) $request->query('limit', 20), 10), 100);

        if ($type !== '' && ! isset(self::TYPE_LABELS[$type])) {
            return ['ok' => false, 'status' => 422, 'message' => 'Loại phúc lợi không hợp lệ'];
        }

        $query = DB::connection('game')->table('phuc_loi_config');
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($active === '0' || $active === '1') {
            $query->where('active', (int) $active);
        }
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search)
                        ->orWhere('ref_id', (int) $search);
                }
                $builder->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('msg_key', 'like', "%{$search}%")
                    ->orWhere('msg_value', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();
        $payload = $rows->map(fn ($row) => $this->serialize($row))->values()->all();

        return [
            'ok' => true,
            'data' => $payload,
            'item_catalog' => $this->catalogForRows($payload),
            'types' => self::TYPE_LABELS,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => max(1, (int) ceil($total / $limit)),
        ];
    }

    public function get(int $id): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }

        $row = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        if (! $row) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy cấu hình phúc lợi'];
        }

        $payload = $this->serialize($row);

        return [
            'ok' => true,
            'data' => $payload,
            'item_catalog' => $this->catalogForRows([$payload]),
            'types' => self::TYPE_LABELS,
        ];
    }

    public function create(array $input): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }
        if ($error = $this->validateBusinessRules($input)) {
            return $error;
        }

        $row = $this->normalizeRow($input);
        try {
            $id = (int) DB::connection('game')->table('phuc_loi_config')->insertGetId($row);
        } catch (QueryException $exception) {
            return $this->writeError($exception);
        }

        $after = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        $this->logAdminAction(
            'create',
            'welfare_config',
            $id,
            'Tạo phúc lợi '.$this->displayName((array) $after),
            null,
            (array) $after
        );

        return ['ok' => true, 'message' => 'Tạo cấu hình phúc lợi thành công', 'id' => $id];
    }

    public function copy(int $id): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }

        $game = DB::connection('game');
        $source = $game->table('phuc_loi_config')->where('id', $id)->first();
        if (! $source) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy cấu hình phúc lợi'];
        }

        $type = (string) $source->type;
        $sourceState = (array) $source;

        try {
            $newId = $game->transaction(function () use ($game, $source, $id, $type, $sourceState): int {
                $isMessage = $type === 'message';
                $nextRefId = $isMessage
                    ? 0
                    : ((int) $game->table('phuc_loi_config')->where('type', $type)->max('ref_id') + 1);
                $nextMsgKey = $isMessage
                    ? $this->nextMessageKey($game, (string) ($source->msg_key ?? ''))
                    : '';
                $nextSortOrder = (int) $game->table('phuc_loi_config')->where('type', $type)->max('sort_order') + 1;
                $label = $isMessage
                    ? ''
                    : $this->copyLabel((string) ($source->label ?? ''), $id);

                $row = [
                    'type' => $type,
                    'ref_id' => $nextRefId,
                    'label' => $label,
                    'description' => $source->description,
                    'price' => (int) $source->price,
                    'rewards_json' => (string) $source->rewards_json,
                    'sort_order' => $nextSortOrder,
                    'active' => (int) $source->active,
                    'msg_key' => $nextMsgKey,
                    'msg_value' => $source->msg_value,
                ];
                $newId = (int) $game->table('phuc_loi_config')->insertGetId($row);
                $after = $game->table('phuc_loi_config')->where('id', $newId)->first();

                $this->logAdminAction(
                    'welfare_config.copy',
                    'welfare_config',
                    $newId,
                    "Sao chép phúc lợi #{$id} thành #{$newId}",
                    $sourceState,
                    (array) $after,
                    ['source_id' => $id],
                );

                return $newId;
            });
        } catch (QueryException $exception) {
            return $this->writeError($exception);
        }

        return [
            'ok' => true,
            'message' => "Đã sao chép mốc phúc lợi #{$id} thành #{$newId}",
            'id' => $newId,
        ];
    }

    public function update(int $id, array $input): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }

        $before = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        if (! $before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy cấu hình phúc lợi'];
        }
        if ($error = $this->validateBusinessRules($input, $id)) {
            return $error;
        }

        try {
            DB::connection('game')->table('phuc_loi_config')->where('id', $id)->update($this->normalizeRow($input));
        } catch (QueryException $exception) {
            return $this->writeError($exception);
        }

        $after = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        $this->logAdminAction(
            'update',
            'welfare_config',
            $id,
            'Cập nhật phúc lợi '.$this->displayName((array) $after),
            (array) $before,
            (array) $after
        );

        return ['ok' => true, 'message' => 'Cập nhật cấu hình phúc lợi thành công'];
    }

    public function toggle(int $id): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }

        $before = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        if (! $before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy cấu hình phúc lợi'];
        }

        $active = (int) ! $before->active;
        DB::connection('game')->table('phuc_loi_config')->where('id', $id)->update(['active' => $active]);
        $after = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        $this->logAdminAction(
            'update',
            'welfare_config',
            $id,
            ($active ? 'Bật' : 'Tắt').' phúc lợi '.$this->displayName((array) $after),
            (array) $before,
            (array) $after
        );

        return [
            'ok' => true,
            'message' => $active ? 'Đã bật cấu hình phúc lợi' : 'Đã tắt cấu hình phúc lợi',
            'active' => (bool) $active,
        ];
    }

    public function delete(int $id): array
    {
        if (! $this->tableExists()) {
            return $this->missingTable();
        }

        $before = DB::connection('game')->table('phuc_loi_config')->where('id', $id)->first();
        if (! $before) {
            return ['ok' => false, 'status' => 404, 'message' => 'Không tìm thấy cấu hình phúc lợi'];
        }

        DB::connection('game')->table('phuc_loi_config')->where('id', $id)->delete();
        $this->logAdminAction(
            'delete',
            'welfare_config',
            $id,
            'Xóa phúc lợi '.$this->displayName((array) $before),
            (array) $before,
            null
        );

        return ['ok' => true, 'message' => 'Đã xóa cấu hình phúc lợi'];
    }

    private function validateBusinessRules(array $input, ?int $ignoreId = null): ?array
    {
        $type = (string) $input['type'];
        $refId = (int) $input['ref_id'];
        $msgKey = $type === 'message' ? trim((string) ($input['msg_key'] ?? '')) : '';

        if (! in_array($type, ['attendance_daily', 'message'], true) && $refId < 1) {
            return ['ok' => false, 'status' => 422, 'message' => 'Mốc/ID tham chiếu phải lớn hơn 0'];
        }

        $duplicate = DB::connection('game')
            ->table('phuc_loi_config')
            ->where('type', $type)
            ->where('ref_id', $type === 'message' ? 0 : $refId)
            ->where('msg_key', $msgKey);
        if ($ignoreId !== null) {
            $duplicate->where('id', '<>', $ignoreId);
        }
        if ($duplicate->exists()) {
            return ['ok' => false, 'status' => 422, 'message' => 'Loại và mốc này đã có cấu hình'];
        }

        if ($type === 'message') {
            return null;
        }

        $itemIds = collect($input['rewards'] ?? [])
            ->pluck('item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $existingIds = DB::connection('game')
            ->table('item_template')
            ->whereIn('id', $itemIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
        $missing = $itemIds->diff($existingIds)->values();
        if ($missing->isNotEmpty()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Item không tồn tại: '.$missing->implode(', '),
            ];
        }

        $optionIds = collect($input['rewards'] ?? [])
            ->flatMap(fn ($reward) => collect($reward['options'] ?? [])->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($optionIds->isNotEmpty()) {
            $existingOptionIds = DB::connection('game')
                ->table('item_option_template')
                ->whereIn('id', $optionIds->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $missingOptions = $optionIds->diff($existingOptionIds)->values();
            if ($missingOptions->isNotEmpty()) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Option không tồn tại: '.$missingOptions->implode(', '),
                ];
            }
        }

        return null;
    }

    private function normalizeRow(array $input): array
    {
        $type = (string) $input['type'];
        $isMessage = $type === 'message';
        $rewards = $isMessage
            ? []
            : collect($input['rewards'] ?? [])->map(fn ($reward) => [
                'item_id' => (int) $reward['item_id'],
                'amount' => (int) $reward['amount'],
                'options' => collect($reward['options'] ?? [])->map(fn ($option) => [
                    'id' => (int) $option['id'],
                    'param' => (int) $option['param'],
                ])->values()->all(),
            ])->values()->all();

        return [
            'type' => $type,
            'ref_id' => $isMessage ? 0 : (int) $input['ref_id'],
            'label' => $isMessage ? '' : trim((string) ($input['label'] ?? '')),
            'description' => $isMessage ? '' : trim((string) ($input['description'] ?? '')),
            'price' => $isMessage ? 0 : (int) $input['price'],
            'rewards_json' => json_encode($rewards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'msg_key' => $isMessage ? trim((string) $input['msg_key']) : '',
            'msg_value' => $isMessage ? trim((string) $input['msg_value']) : '',
            'sort_order' => (int) $input['sort_order'],
            'active' => (int) (bool) $input['active'],
        ];
    }

    private function serialize(object $row): array
    {
        $decoded = json_decode((string) ($row->rewards_json ?? '[]'), true);
        $rewards = is_array($decoded)
            ? collect($decoded)->filter(fn ($reward) => is_array($reward))
                ->map(fn ($reward) => [
                    'item_id' => (int) ($reward['item_id'] ?? 0),
                    'amount' => max(1, (int) ($reward['amount'] ?? 1)),
                    'options' => collect($reward['options'] ?? [])->filter(fn ($option) => is_array($option))
                        ->map(fn ($option) => [
                            'id' => (int) ($option['id'] ?? 0),
                            'param' => max(0, (int) ($option['param'] ?? 0)),
                        ])->values()->all(),
                ])->values()->all()
            : [];

        return [
            'id' => (int) $row->id,
            'type' => (string) $row->type,
            'type_label' => self::TYPE_LABELS[(string) $row->type] ?? (string) $row->type,
            'ref_id' => (int) $row->ref_id,
            'label' => (string) ($row->label ?? ''),
            'description' => (string) ($row->description ?? ''),
            'price' => (int) $row->price,
            'rewards' => $rewards,
            'reward_count' => count($rewards),
            'msg_key' => (string) ($row->msg_key ?? ''),
            'msg_value' => (string) ($row->msg_value ?? ''),
            'sort_order' => (int) $row->sort_order,
            'active' => (bool) $row->active,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function catalogForRows(array $rows): array
    {
        $ids = collect($rows)
            ->flatMap(fn ($row) => collect($row['rewards'] ?? [])->pluck('item_id'))
            ->unique()
            ->values()
            ->all();

        return $ids ? $this->catalog->batchItems(implode(',', $ids)) : [];
    }

    private function displayName(array $row): string
    {
        if (($row['type'] ?? '') === 'message') {
            return (string) ($row['msg_key'] ?? $row['id'] ?? '');
        }

        return trim((string) ($row['label'] ?? '')) ?: (string) ($row['ref_id'] ?? $row['id'] ?? '');
    }

    private function nextMessageKey($game, string $sourceKey): string
    {
        $base = trim($sourceKey) !== '' ? trim($sourceKey).'_copy' : 'message_copy';
        $candidate = substr($base, 0, 64);
        $suffix = 2;

        while ($game->table('phuc_loi_config')->where('type', 'message')->where('msg_key', $candidate)->exists()) {
            $suffixText = '_'.$suffix;
            $candidate = substr(substr($base, 0, 64 - strlen($suffixText)).$suffixText, 0, 64);
            $suffix++;
        }

        return $candidate;
    }

    private function copyLabel(string $label, int $sourceId): string
    {
        $suffix = ' (bản sao)';
        $label = trim($label);
        if ($label === '') {
            return "Bản sao #{$sourceId}";
        }

        return mb_substr($label, 0, 255 - mb_strlen($suffix)).$suffix;
    }

    private function tableExists(): bool
    {
        return Schema::connection('game')->hasTable('phuc_loi_config');
    }

    private function missingTable(): array
    {
        return [
            'ok' => false,
            'status' => 503,
            'message' => 'Chưa có bảng phuc_loi_config. Hãy chạy game server một lần để khởi tạo dữ liệu phúc lợi.',
        ];
    }

    private function writeError(QueryException $exception): array
    {
        $duplicate = str_contains(strtolower($exception->getMessage()), 'duplicate');

        return [
            'ok' => false,
            'status' => 422,
            'message' => $duplicate
                ? 'Loại và mốc này đã có cấu hình'
                : 'Không thể lưu cấu hình phúc lợi',
        ];
    }
}
