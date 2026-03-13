<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ideas', 'priority')) {
            return;
        }

        Schema::table('ideas', function (Blueprint $table) {
            $table->string('priority', 20)->default('medium')->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ideas', 'priority')) {
            return;
        }

        Schema::table('ideas', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};

