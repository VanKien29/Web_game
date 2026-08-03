<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HiddenOptionGroupService extends AdminServiceSupport
{
    private const MARKER_OPTION_ID = 210;

    public function list(Request $request): array
    {
        if (! $this->tablesReady()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Chưa có bảng hidden_option_group trong game DB.',
            ];
        }

        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $active = $request->query('active');
        $game = DB::connection('game');

        $query = $game->table('hidden_option_group');
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                if (is_numeric($search)) {
                    $builder->where('id', (int) $search);
                } else {
                    $builder->where('name', 'like', "%{$search}%");
                }
            });
        }
        if ($active !== null && $active !== '') {
            $query->where('is_active', (int) ((bool) $active));
        }

        $total = (clone $query)->count('id');
        $groups = $query->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $data = $this->appendDetails($groups);

        return [
            'ok' => true,
            'data' => $data,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function get(int $id): array
    {
        if (! $this->tablesReady()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Chưa có bảng hidden_option_group trong game DB.',
            ];
        }

        $group = DB::connection('game')
            ->table('hidden_option_group')
            ->where('id', $id)
            ->first();

        if (! $group) {
            return ['ok' => false, 'status' => 404, 'message' => 'Group option ẩn không tồn tại.'];
        }

        return ['ok' => true, 'data' => $this->appendDetails(collect([$group]))->first()];
    }

    public function create(Request $request): array
    {
        return $this->save($request, null);
    }

    public function update(Request $request, int $id): array
    {
        return $this->save($request, $id);
    }

    public function copy(int $id): array
    {
        if (! $this->tablesReady()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Chưa có bảng hidden_option_group trong game DB.',
            ];
        }

        $game = DB::connection('game');
        $source = $game->table('hidden_option_group')->where('id', $id)->first();
        if (! $source) {
            return ['ok' => false, 'status' => 404, 'message' => 'Group option ẩn không tồn tại.'];
        }

        $details = $game->table('hidden_option_group_detail')
            ->where('group_id', $id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $sourceState = $this->snapshot($id);
        $name = $this->copyName((string) $source->name);
        $hasParamMin = Schema::connection('game')->hasColumn('hidden_option_group_detail', 'param_min');
        $hasParamMax = Schema::connection('game')->hasColumn('hidden_option_group_detail', 'param_max');

        try {
            $newId = $game->transaction(function () use (
                $game,
                $source,
                $details,
                $id,
                $name,
                $hasParamMin,
                $hasParamMax,
                $sourceState,
            ): int {
                $newId = (int) $game->table('hidden_option_group')->insertGetId([
                    'name' => $name,
                    'roll_count' => (int) $source->roll_count,
                    'is_active' => (int) $source->is_active,
                ]);

                if ($details->isNotEmpty()) {
                    $payload = $details->map(function ($detail) use ($newId, $hasParamMin, $hasParamMax): array {
                        $paramMin = $detail->param_min ?? $detail->param ?? 0;
                        $row = [
                            'group_id' => $newId,
                            'option_id' => (int) $detail->option_id,
                            'param' => (int) $paramMin,
                            'sort_order' => (int) $detail->sort_order,
                            'is_active' => (int) $detail->is_active,
                        ];
                        if ($hasParamMin) {
                            $row['param_min'] = (int) $paramMin;
                        }
                        if ($hasParamMax) {
                            $row['param_max'] = $detail->param_max === null ? null : (int) $detail->param_max;
                        }

                        return $row;
                    })->all();

                    $game->table('hidden_option_group_detail')->insert($payload);
                }

                $this->logAdminAction(
                    'hidden_option_group.copy',
                    'hidden_option_group',
                    $newId,
                    "Sao chép group option ẩn #{$id} thành #{$newId}",
                    $sourceState,
                    $this->snapshot($newId),
                    ['source_group_id' => $id],
                );

                return $newId;
            });
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 500,
                'message' => 'Không thể sao chép group option ẩn: '.$e->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'message' => "Đã sao chép group #{$id} thành group #{$newId}.",
            'data' => $this->get($newId)['data'] ?? null,
        ];
    }

    public function delete(int $id): array
    {
        if (! $this->tablesReady()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Chưa có bảng hidden_option_group trong game DB.',
            ];
        }

        $game = DB::connection('game');
        $before = $this->snapshot($id);
        if ($before === null) {
            return ['ok' => false, 'status' => 404, 'message' => 'Group option ẩn không tồn tại.'];
        }

        try {
            $game->transaction(function () use ($game, $id, $before): void {
                $game->table('hidden_option_group_detail')->where('group_id', $id)->delete();
                $game->table('hidden_option_group')->where('id', $id)->delete();

                $this->logAdminAction(
                    'hidden_option_group.delete',
                    'hidden_option_group',
                    $id,
                    "Xóa group option ẩn #{$id}",
                    $before,
                    null,
                );
            });
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 500,
                'message' => 'Không thể xóa group option ẩn: '.$e->getMessage(),
            ];
        }

        return ['ok' => true, 'message' => 'Đã xóa group option ẩn.'];
    }

    private function save(Request $request, ?int $id): array
    {
        if (! $this->tablesReady()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Chưa có bảng hidden_option_group trong game DB.',
            ];
        }

        $name = trim((string) $request->input('name', ''));
        $rollCount = max(1, (int) $request->input('roll_count', 1));
        $isActive = $request->boolean('is_active', true) ? 1 : 0;
        $options = $this->normalizeOptions((array) $request->input('options', []));

        $businessError = $this->validateOptions($options, $rollCount);
        if ($businessError !== null) {
            return $businessError;
        }

        $game = DB::connection('game');
        $before = $id === null ? null : $this->snapshot($id);
        if ($id !== null && $before === null) {
            return ['ok' => false, 'status' => 404, 'message' => 'Group option ẩn không tồn tại.'];
        }

        try {
            $savedId = $game->transaction(function () use ($game, $id, $name, $rollCount, $isActive, $options, $before): int {
                if ($id === null) {
                    $savedId = (int) $game->table('hidden_option_group')->insertGetId([
                        'name' => $name,
                        'roll_count' => $rollCount,
                        'is_active' => $isActive,
                    ]);
                } else {
                    $game->table('hidden_option_group')->where('id', $id)->update([
                        'name' => $name,
                        'roll_count' => $rollCount,
                        'is_active' => $isActive,
                    ]);
                    $savedId = $id;
                    $game->table('hidden_option_group_detail')->where('group_id', $savedId)->delete();
                }

                $game->table('hidden_option_group_detail')->insert(
                    array_map(fn (array $option): array => $this->detailPayload($savedId, $option), $options),
                );

                $this->logAdminAction(
                    $id === null ? 'hidden_option_group.create' : 'hidden_option_group.update',
                    'hidden_option_group',
                    $savedId,
                    ($id === null ? 'Tạo' : 'Cập nhật')." group option ẩn #{$savedId}",
                    $before,
                    $this->snapshot($savedId),
                );

                return $savedId;
            });
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 500,
                'message' => 'Không thể lưu group option ẩn: '.$e->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'message' => $id === null ? 'Đã tạo group option ẩn.' : 'Đã cập nhật group option ẩn.',
            'data' => $this->get($savedId)['data'] ?? null,
        ];
    }

    private function validateOptions(array $options, int $rollCount): ?array
    {
        foreach ($options as $index => $option) {
            if ($option['param_max'] !== null && $option['param_max'] < $option['param_min']) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => "Dòng option ".($index + 1).": Param max phải lớn hơn hoặc bằng Param min.",
                ];
            }
        }

        if (count($options) < $rollCount) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => "Pool phải có ít nhất {$rollCount} option để random.",
            ];
        }

        $ids = array_column($options, 'id');
        if (count($ids) !== count(array_unique($ids))) {
            return ['ok' => false, 'status' => 422, 'message' => 'Không được trùng option trong cùng một pool.'];
        }
        if (in_array(self::MARKER_OPTION_ID, $ids, true)) {
            return ['ok' => false, 'status' => 422, 'message' => 'Option 210 là marker group, không được đưa vào pool random.'];
        }

        $existingIds = DB::connection('game')
            ->table('item_option_template')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($optionId): int => (int) $optionId)
            ->all();
        $missingIds = array_values(array_diff($ids, $existingIds));
        if ($missingIds !== []) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Option không tồn tại: '.implode(', ', $missingIds),
            ];
        }

        return null;
    }

    private function normalizeOptions(array $options): array
    {
        return array_values(array_map(
            fn (array $option, int $index): array => [
                'id' => (int) ($option['id'] ?? 0),
                'param_min' => max(0, (int) ($option['param_min'] ?? $option['param'] ?? 0)),
                'param_max' => $this->nullableInteger($option['param_max'] ?? null),
                'sort_order' => (int) ($option['sort_order'] ?? $index),
            ],
            $options,
            array_keys($options),
        ));
    }

    private function appendDetails($groups)
    {
        $groups = collect($groups);
        $groupIds = $groups->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if ($groupIds === []) {
            return $groups;
        }

        $details = DB::connection('game')
            ->table('hidden_option_group_detail as d')
            ->leftJoin('item_option_template as o', 'o.id', '=', 'd.option_id')
            ->whereIn('d.group_id', $groupIds)
            ->where('d.is_active', 1)
            ->orderBy('d.sort_order')
            ->orderBy('d.id')
            ->select('d.id', 'd.group_id', 'd.option_id', 'd.param', 'd.param_min', 'd.param_max', 'd.sort_order', 'd.is_active', 'o.name as option_name')
            ->get()
            ->groupBy('group_id');

        return $groups->map(function ($group) use ($details) {
            $groupId = (int) $group->id;
            $group->id = $groupId;
            $group->roll_count = (int) $group->roll_count;
            $group->is_active = (bool) $group->is_active;
            $group->options = collect($details->get($groupId, []))->map(function ($detail) {
                $paramMin = $detail->param_min === null
                    ? (int) $detail->param
                    : (int) $detail->param_min;

                return [
                    'id' => (int) $detail->id,
                    'group_id' => (int) $detail->group_id,
                    'option_id' => (int) $detail->option_id,
                    'param' => $paramMin,
                    'param_min' => $paramMin,
                    'param_max' => $detail->param_max === null ? null : (int) $detail->param_max,
                    'sort_order' => (int) $detail->sort_order,
                    'is_active' => (bool) $detail->is_active,
                    'option_name' => (string) ($detail->option_name ?? ''),
                ];
            })->values()->all();
            $group->option_count = count($group->options);

            return $group;
        })->values();
    }

    private function detailPayload(int $groupId, array $option): array
    {
        $payload = [
            'group_id' => $groupId,
            'option_id' => $option['id'],
            'param' => $option['param_min'],
            'sort_order' => $option['sort_order'],
            'is_active' => 1,
        ];

        $schema = Schema::connection('game');
        if ($schema->hasColumn('hidden_option_group_detail', 'param_min')) {
            $payload['param_min'] = $option['param_min'];
        }
        if ($schema->hasColumn('hidden_option_group_detail', 'param_max')) {
            $payload['param_max'] = $option['param_max'];
        }

        return $payload;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function copyName(string $name): string
    {
        $suffix = ' (Bản sao)';
        $maxNameLength = 255;

        if (mb_strlen($name) + mb_strlen($suffix) <= $maxNameLength) {
            return $name.$suffix;
        }

        return mb_substr($name, 0, $maxNameLength - mb_strlen($suffix)).$suffix;
    }

    private function snapshot(int $id): ?array
    {
        $group = DB::connection('game')->table('hidden_option_group')->where('id', $id)->first();
        if (! $group) {
            return null;
        }

        $data = $this->appendDetails(collect([$group]))->first();

        return $this->sanitizeLogState((array) $data);
    }

    private function tablesReady(): bool
    {
        try {
            $schema = Schema::connection('game');

            return $schema->hasTable('hidden_option_group')
                && $schema->hasTable('hidden_option_group_detail');
        } catch (\Throwable) {
            return false;
        }
    }
}
