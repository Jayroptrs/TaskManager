<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_due_date_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_due_date_reminders');
    }
};
