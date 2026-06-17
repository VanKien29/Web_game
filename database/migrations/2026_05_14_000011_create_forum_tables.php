<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['announcement', 'player_post', 'feedback'])->default('player_post');
            $table->unsignedInteger('nro_account_id')->nullable();
            $table->string('author_username', 50);
            $table->string('author_avatar', 500)->nullable();
            $table->string('title', 160)->nullable();
            $table->longText('content');
            $table->json('images')->nullable();
            $table->enum('status', ['published', 'pending', 'hidden', 'deleted'])->default('published');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('reaction_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->timestamps();
            $table->timestamp('published_at')->nullable();

            $table->index(['status', 'type']);
            $table->index(['is_pinned', 'created_at']);
            $table->index('nro_account_id');
        });

        Schema::create('forum_post_reactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('forum_post_id');
            $table->unsignedInteger('nro_account_id');
            $table->enum('type', ['like', 'love', 'haha', 'wow', 'sad', 'angry'])->default('like');
            $table->timestamps();

            $table->unique(['forum_post_id', 'nro_account_id'], 'forum_post_reaction_unique');
            $table->foreign('forum_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
            $table->index('nro_account_id');
        });

        Schema::create('forum_post_saves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('forum_post_id');
            $table->unsignedInteger('nro_account_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['forum_post_id', 'nro_account_id'], 'forum_post_save_unique');
            $table->foreign('forum_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
            $table->index('nro_account_id');
        });

        Schema::create('forum_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('forum_post_id');
            $table->unsignedInteger('parent_comment_id')->nullable();
            $table->unsignedInteger('nro_account_id');
            $table->string('username', 50);
            $table->string('avatar_url', 500)->nullable();
            $table->text('content');
            $table->enum('status', ['visible', 'hidden', 'deleted'])->default('visible');
            $table->unsignedInteger('likes')->default(0);
            $table->timestamps();

            $table->foreign('forum_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
            $table->foreign('parent_comment_id')->references('id')->on('forum_comments')->onDelete('cascade');
            $table->index(['forum_post_id', 'status']);
            $table->index('nro_account_id');
        });

        Schema::create('forum_comment_reactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('forum_comment_id');
            $table->unsignedInteger('nro_account_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['forum_comment_id', 'nro_account_id'], 'forum_comment_reaction_unique');
            $table->foreign('forum_comment_id')->references('id')->on('forum_comments')->onDelete('cascade');
            $table->index('nro_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comment_reactions');
        Schema::dropIfExists('forum_comments');
        Schema::dropIfExists('forum_post_saves');
        Schema::dropIfExists('forum_post_reactions');
        Schema::dropIfExists('forum_posts');
    }
};
