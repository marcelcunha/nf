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
        Schema::table('nfe_imports', function (Blueprint $table) {
            $table->timestamp('price_table_published_at')->nullable()->after('items_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfe_imports', function (Blueprint $table) {
            $table->dropColumn('price_table_published_at');
        });
    }
};
