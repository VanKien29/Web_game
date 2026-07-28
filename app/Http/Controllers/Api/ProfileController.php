<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\HeadAvatar;
use App\Models\Game\TaskMainTemplate;
use App\Services\ProfileAppearanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileAppearanceService $appearance,
    ) {}

    public function profile(Request $request): JsonResponse
    {
        $account = $request->get('game_user');
        $player = $account->player;

        if (! $player) {
            return response()->json([
                'ok' => true,
                'data' => [
                    'user' => [
                        'id' => (int) $account->id,
                        'username' => $account->username,
                        'email' => $account->email ?? null,
                        'cash' => (int) $account->cash,
                        'danap' => (int) $account->danap,
                        'active' => (int) $account->active,
                    ],
                    'player' => [
                        'has_character' => false,
                    ],
                ],
            ])->header('Cache-Control', 'private, no-store');
        }

        $pointData = $this->decodeArray($player->data_point ?? null);
        $inventoryData = $this->decodeArray($player->data_inventory ?? null);
        $thoiVang = Schema::connection('game')->hasColumn('account', 'thoi_vang')
            ? (int) ($account->thoi_vang ?? 0)
            : 0;

        $power = isset($pointData[1]) ? (int) $pointData[1] : (int) $player->power;

        // Nhiệm vụ
        $taskName = 'Chưa có nhiệm vụ';
        $taskData = $player->task_data;
        if (array_key_exists(0, $taskData) && $taskData[0] !== '' && $taskData[0] !== null) {
            $taskId = (int) $taskData[0];
            $task = TaskMainTemplate::find($taskId);
            if ($task) {
                $taskName = $task->getAttribute('NAME')
                    ?? $task->getAttribute('name')
                    ?? $taskName;
            } else {
                $taskName = 'Nhiệm vụ #'.$taskId;
            }
        }

        // Giới tính
        $genderText = match ((int) $player->gender) {
            0 => 'Trái Đất',
            1 => 'Namec',
            2 => 'Xayda',
            default => 'Không xác định',
        };

        // Avatar
        $avatarUrl = '/assets/frontend/home/v1/images/bannergame.png';
        if (! empty($player->head)) {
            $headAvatar = HeadAvatar::where('head_id', $player->head)->first();
            if ($headAvatar) {
                $avatarUrl = '/assets/frontend/home/v1/images/x4/'.$headAvatar->avatar_id.'.png';
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'user' => [
                    'username' => $account->username,
                    'email' => $account->email ?? null,
                    'cash' => (int) ($account->cash ?? 0),
                    'danap' => (int) ($account->danap ?? 0),
                    'active' => (int) ($account->active ?? 0),
                ],
                'player' => [
                    'has_character' => true,
                    'name' => $player->name,
                    'power' => $power,
                    'task_name' => $taskName,
                    'gender_text' => $genderText,
                    'avatar_url' => $avatarUrl,
                    'appearance' => $this->appearance->resolve($player),
                    'stats' => [
                        'potential' => (int) ($pointData[2] ?? 0),
                        'hp' => (int) ($pointData[5] ?? 0),
                        'ki' => (int) ($pointData[6] ?? 0),
                        'damage' => (int) ($pointData[7] ?? 0),
                        'defense' => (int) ($pointData[8] ?? 0),
                        'critical' => (int) ($pointData[9] ?? 0),
                    ],
                    'inventory' => [
                        'gold' => (int) ($inventoryData[0] ?? 0),
                        'gem' => (int) ($inventoryData[1] ?? 0),
                        'ruby' => (int) ($inventoryData[2] ?? 0),
                        'thoi_vang' => $thoiVang,
                    ],
                ],
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    private function decodeArray($value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($this->fixJson($value), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function fixJson(string $value): string
    {
        $normalized = trim($value);
        $normalized = preg_replace('/,\s*([\]\}])/', '$1', $normalized);
        $normalized = preg_replace('/([\[\{])\s*,/', '$1', $normalized);
        $normalized = preg_replace('/,\s*,/', ',', $normalized);

        return $normalized ?: '[]';
    }
}
