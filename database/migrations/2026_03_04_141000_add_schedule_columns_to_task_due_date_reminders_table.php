<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('task_due_date_reminders', 'days_before')) {
            Schema::table('task_due_date_reminders', function (Blueprint $table) {
                $table->unsignedSmallInteger('days_before')->default(0)->after('due_date');
            });
        }

        if (! Schema::hasColumn('task_due_date_reminders', 'remind_on_date')) {
            Schema::table('task_due_date_reminders', function (Blueprint $table) {
                $table->date('remind_on_date')->nullable()->after('days_before');
            });
        }

        DB::table('task_due_date_reminders')->update([
            'remind_on_date' => DB::raw('due_date'),
        ]);

        Schema::table('task_due_date_reminders', function (Blueprint $table) {
            try {
                $table->index('task_id', 'task_due_date_reminders_task_id_index');
            } catch (\Throwable) {
                // Index may already exist.
            }

            try {
                $table->dropUnique('task_due_date_reminders_task_id_user_id_unique');
            } catch (\Throwable) {
                // Migration may already be partially applied.
            }

            try {
                $table->unique(['task_id', 'user_id', 'days_before'], 'task_due_date_reminders_task_user_day_unique');
            } catch (\Throwable) {
                // Index may already exist.
            }

            try {
                $table->index('remind_on_date', 'task_due_date_reminders_remind_on_date_index');
            } catch (\Throwable) {
                // Index may already exist.
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_due_date_reminders', function (Blueprint $table) {
            try {
                $table->dropUnique('task_due_date_reminders_task_user_day_unique');
            } catch (\Throwable) {
                // Ignore if not present.
            }

            try {
                $table->dropIndex('task_due_date_reminders_remind_on_date_index');
            } catch (\Throwable) {
                // Ignore if not present.
            }

            if (Schema::hasColumn('task_due_date_reminders', 'days_before')) {
                $table->dropColumn('days_before');
            }
            if (Schema::hasColumn('task_due_date_reminders', 'remind_on_date')) {
                $table->dropColumn('remind_on_date');
            }

            try {
                $table->unique(['task_id', 'user_id']);
            } catch (\Throwable) {
                // Ignore if already present.
            }
        });
    }
};
