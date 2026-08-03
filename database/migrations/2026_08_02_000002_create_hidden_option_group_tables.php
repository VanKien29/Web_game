<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $game = Schema::connection('game');

        if (! $game->hasTable('hidden_option_group')) {
            $game->create('hidden_option_group', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 255);
                $table->smallInteger('roll_count')->default(1);
                $table->boolean('is_active')->default(true);
            });
        }

        if (! $game->hasTable('hidden_option_group_detail')) {
            $game->create('hidden_option_group_detail', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('group_id');
                $table->integer('option_id');
                $table->integer('param')->default(0);
                $table->integer('param_min')->nullable();
                $table->integer('param_max')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->index('group_id');
                $table->foreign('group_id')
                    ->references('id')
                    ->on('hidden_option_group')
                    ->cascadeOnDelete();
            });
        }

        if ($game->hasTable('hidden_option_group_detail')) {
            if (! $game->hasColumn('hidden_option_group_detail', 'param_min')) {
                $game->table('hidden_option_group_detail', function (Blueprint $table): void {
                    $table->integer('param_min')->nullable()->after('param');
                });
            }
            if (! $game->hasColumn('hidden_option_group_detail', 'param_max')) {
                $game->table('hidden_option_group_detail', function (Blueprint $table): void {
                    $table->integer('param_max')->nullable()->after('param_min');
                });
            }

            DB::connection('game')->statement(
                'UPDATE hidden_option_group_detail SET param_min = param WHERE param_min IS NULL',
            );
        }
    }

    public function down(): void
    {
        $game = Schema::connection('game');
        $game->dropIfExists('hidden_option_group_detail');
        $game->dropIfExists('hidden_option_group');
    }
};
