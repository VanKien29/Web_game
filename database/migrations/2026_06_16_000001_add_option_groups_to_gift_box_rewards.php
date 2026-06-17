<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('game')->hasTable('gift_box_rewards')) {
            return;
        }

        if (!Schema::connection('game')->hasColumn('gift_box_rewards', 'option_groups_json')) {
            Schema::connection('game')->table('gift_box_rewards', function (Blueprint $table) {
                $table->text('option_groups_json')->nullable()->after('options_json');
            });
        }

        $this->compactSeeded1967VipReward();
    }

    public function down(): void
    {
        if (!Schema::connection('game')->hasTable('gift_box_rewards')) {
            return;
        }

        if (Schema::connection('game')->hasColumn('gift_box_rewards', 'option_groups_json')) {
            Schema::connection('game')->table('gift_box_rewards', function (Blueprint $table) {
                $table->dropColumn('option_groups_json');
            });
        }
    }

    private function compactSeeded1967VipReward(): void
    {
        $game = DB::connection('game');
        if (!Schema::connection('game')->hasTable('gift_box_configs')) {
            return;
        }

        $box = $game->table('gift_box_configs')->where('item_id', 1967)->first();
        if (!$box) {
            return;
        }

        $vipRows = $game->table('gift_box_rewards')
            ->where('gift_box_config_id', $box->id)
            ->where('reward_item_id', 1966)
            ->get();

        if ($vipRows->count() <= 1) {
            return;
        }

        $now = now();
        $sortOrder = (int) $vipRows->min('sort_order');
        $game->table('gift_box_rewards')
            ->where('gift_box_config_id', $box->id)
            ->where('reward_item_id', 1966)
            ->delete();

        $game->table('gift_box_rewards')->insert([
            'gift_box_config_id' => $box->id,
            'reward_item_id' => 1966,
            'quantity_min' => 1,
            'quantity_max' => 1,
            'chance_weight' => 6000,
            'options_json' => '[]',
            'option_groups_json' => json_encode($this->seeded1967OptionGroups(), JSON_UNESCAPED_UNICODE),
            'sort_order' => $sortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seeded1967OptionGroups(): array
    {
        return [
            [
                'name' => 'Hạn sử dụng',
                'entries' => [
                    ['label' => '1 ngày', 'chance_weight' => 700, 'options' => [['id' => 93, 'param_min' => 1, 'param_max' => 1]]],
                    ['label' => '3 ngày', 'chance_weight' => 500, 'options' => [['id' => 93, 'param_min' => 3, 'param_max' => 3]]],
                    ['label' => '5 ngày', 'chance_weight' => 360, 'options' => [['id' => 93, 'param_min' => 5, 'param_max' => 5]]],
                    ['label' => '7 ngày', 'chance_weight' => 240, 'options' => [['id' => 93, 'param_min' => 7, 'param_max' => 7]]],
                    ['label' => '15 ngày', 'chance_weight' => 160, 'options' => [['id' => 93, 'param_min' => 15, 'param_max' => 15]]],
                    ['label' => 'Vĩnh viễn', 'chance_weight' => 40, 'options' => []],
                ],
            ],
            [
                'name' => 'Combo chỉ số',
                'entries' => [
                    ['label' => 'Combo 1', 'chance_weight' => 1, 'options' => [['id' => 50, 'param_min' => 1, 'param_max' => 5], ['id' => 0, 'param_min' => 500, 'param_max' => 5000]]],
                    ['label' => 'Combo 2', 'chance_weight' => 1, 'options' => [['id' => 77, 'param_min' => 1, 'param_max' => 5], ['id' => 6, 'param_min' => 5000, 'param_max' => 50000]]],
                    ['label' => 'Combo 3', 'chance_weight' => 1, 'options' => [['id' => 103, 'param_min' => 1, 'param_max' => 5], ['id' => 7, 'param_min' => 5000, 'param_max' => 50000]]],
                ],
            ],
        ];
    }
};
