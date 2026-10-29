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
        Schema::table('sefaz_sync_states', function (Blueprint $table) {
            $table->json('schedule_times')->nullable()->after('blocked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sefaz_sync_states', function (Blueprint $table) {
            $table->dropColumn('schedule_times');
        });
    }
};
