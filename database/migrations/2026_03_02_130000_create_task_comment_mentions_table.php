<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_comment_id')->constrained('task_comments')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentioned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['task_comment_id', 'mentioned_user_id'], 'task_comment_mentions_unique_user_per_comment');
            $table->index(['mentioned_user_id', 'read_at'], 'task_comment_mentions_user_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comment_mentions');
    }
};
