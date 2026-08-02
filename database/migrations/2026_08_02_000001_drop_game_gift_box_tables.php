<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('game')->dropIfExists('gift_box_rewards');
        Schema::connection('game')->dropIfExists('gift_box_configs');
    }

    public function down(): void
    {
        // Gift box rewards are permanently managed in Source_game/src.
    }
};
