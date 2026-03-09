<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_comment_id')->constrained('task_comments')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comment_attachments');
    }
};
