<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->timestamp('admin_resolved_at')->nullable()->after('resolved_at');
            $table->timestamp('user_resolved_at')->nullable()->after('admin_resolved_at');
            $table->index('admin_resolved_at');
            $table->index('user_resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['admin_resolved_at']);
            $table->dropIndex(['user_resolved_at']);
            $table->dropColumn(['admin_resolved_at', 'user_resolved_at']);
        });
    }
};

