<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password')->index();
        });

        $adminEmails = array_values(array_filter(array_map(
            fn (string $email) => strtolower(trim($email)),
            explode(',', (string) env('ADMIN_EMAILS', ''))
        )));

        if ($adminEmails !== []) {
            DB::table('users')
                ->whereIn(DB::raw('LOWER(email)'), $adminEmails)
                ->update(['is_admin' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
