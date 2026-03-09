<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['idea_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activity_logs');
    }
};

