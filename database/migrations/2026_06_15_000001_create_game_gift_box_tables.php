<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('game')->create('gift_box_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_id')->unique();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('min_empty_slots')->default(1);
            $table->string('success_message', 255)->default('Bạn mở rương nhận được {item}');
            $table->timestamps();

            $table->index(['active', 'item_id']);
        });

        Schema::connection('game')->create('gift_box_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_box_config_id')->constrained('gift_box_configs')->cascadeOnDelete();
            $table->unsignedInteger('reward_item_id');
            $table->unsignedInteger('quantity_min')->default(1);
            $table->unsignedInteger('quantity_max')->default(1);
            $table->unsignedInteger('chance_weight')->default(1);
            $table->text('options_json')->nullable();
            $table->text('option_groups_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['gift_box_config_id', 'sort_order']);
            $table->index('reward_item_id');
        });

        $this->seedGiftBox1967();
    }

    public function down(): void
    {
        Schema::connection('game')->dropIfExists('gift_box_rewards');
        Schema::connection('game')->dropIfExists('gift_box_configs');
    }

    private function seedGiftBox1967(): void
    {
        $game = DB::connection('game');
        if (!$game->table('item_template')->where('id', 1967)->exists()) {
            return;
        }

        $boxId = $game->table('gift_box_configs')->insertGetId([
            'item_id' => 1967,
            'name' => 'Rương Khúc Hoa Tàn Lụi',
            'description' => 'Mở ra các vật phẩm mà bạn không ngờ tới',
            'active' => 1,
            'min_empty_slots' => 1,
            'success_message' => 'Bạn mở rương nhận được {item}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = [];
        $now = now();
        $sort = 0;
        foreach ([17, 18, 19, 20] as $itemId) {
            $rows[] = $this->rewardRow($boxId, $itemId, 4500, [['id' => 30, 'param_min' => 1, 'param_max' => 1]], $sort++, $now);
        }
        foreach ([220, 221, 222, 223, 224, 225] as $itemId) {
            $rows[] = $this->rewardRow($boxId, $itemId, 1000, [['id' => 30, 'param_min' => 1, 'param_max' => 1]], $sort++, $now);
        }

        $hsdWeights = [
            ['days' => 1, 'weight' => 700],
            ['days' => 3, 'weight' => 500],
            ['days' => 5, 'weight' => 360],
            ['days' => 7, 'weight' => 240],
            ['days' => 15, 'weight' => 160],
            ['days' => null, 'weight' => 40],
        ];
        $combos = [
            [['id' => 50, 'param_min' => 1, 'param_max' => 5], ['id' => 0, 'param_min' => 500, 'param_max' => 5000]],
            [['id' => 77, 'param_min' => 1, 'param_max' => 5], ['id' => 6, 'param_min' => 5000, 'param_max' => 50000]],
            [['id' => 103, 'param_min' => 1, 'param_max' => 5], ['id' => 7, 'param_min' => 5000, 'param_max' => 50000]],
        ];

        $hsdEntries = array_map(function (array $hsd) {
            return [
                'label' => $hsd['days'] === null ? 'Vĩnh viễn' : $hsd['days'] . ' ngày',
                'chance_weight' => $hsd['weight'],
                'options' => $hsd['days'] === null ? [] : [['id' => 93, 'param_min' => $hsd['days'], 'param_max' => $hsd['days']]],
            ];
        }, $hsdWeights);
        $comboEntries = array_map(fn(array $combo, int $index) => [
            'label' => 'Combo ' . ($index + 1),
            'chance_weight' => 1,
            'options' => $combo,
        ], $combos, array_keys($combos));
        $rows[] = $this->rewardRow($boxId, 1966, 6000, [], $sort++, $now, [
            ['name' => 'Hạn sử dụng', 'entries' => $hsdEntries],
            ['name' => 'Combo chỉ số', 'entries' => $comboEntries],
        ]);

        $game->table('gift_box_rewards')->insert($rows);
    }

    private function rewardRow(int $boxId, int $itemId, int $weight, array $options, int $sort, mixed $now, array $optionGroups = []): array
    {
        return [
            'gift_box_config_id' => $boxId,
            'reward_item_id' => $itemId,
            'quantity_min' => 1,
            'quantity_max' => 1,
            'chance_weight' => $weight,
            'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'option_groups_json' => json_encode($optionGroups, JSON_UNESCAPED_UNICODE),
            'sort_order' => $sort,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
};
