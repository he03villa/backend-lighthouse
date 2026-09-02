<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('forum_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('forum_posts')->cascadeOnDelete();
        });

        Schema::create('forum_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id')->nullable();
            $table->uuid('comment_id')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('forum_posts')->cascadeOnDelete();
            $table->foreign('comment_id')->references('id')->on('forum_comments')->cascadeOnDelete();
            $table->unique(['post_id', 'comment_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_reactions');
        Schema::dropIfExists('forum_comments');
        Schema::dropIfExists('forum_posts');
    }
};
