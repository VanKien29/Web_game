<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_post_reads', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('forum_post_id');
            $table->unsignedInteger('nro_account_id');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['forum_post_id', 'nro_account_id'], 'forum_post_read_unique');
            $table->foreign('forum_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
            $table->index('nro_account_id');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_reads');
    }
};
