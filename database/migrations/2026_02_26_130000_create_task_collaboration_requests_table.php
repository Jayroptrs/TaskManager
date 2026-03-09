<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_collaboration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invitee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['invitee_id', 'status']);
            $table->unique(['task_id', 'invitee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_collaboration_requests');
    }
};

