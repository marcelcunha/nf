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
        Schema::table('nfe_imports', function (Blueprint $table) {
            $table->unsignedInteger('items_count')->default(0)->after('total');
        });

        DB::table('nfe_imports')->select(['id', 'xml'])->orderBy('id')->each(function (object $import): void {
            $count = substr_count($import->xml, '<det ');

            DB::table('nfe_imports')->where('id', $import->id)->update(['items_count' => $count]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfe_imports', function (Blueprint $table) {
            $table->dropColumn('items_count');
        });
    }
};
