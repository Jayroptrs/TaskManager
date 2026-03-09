<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->foreignId('parent_comment_id')
                ->nullable()
                ->after('body')
                ->constrained('task_comments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_comment_id');
        });
    }
};
