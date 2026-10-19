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
        Schema::create('sefaz_sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('last_nsu', 15)->default('0');
            $table->string('max_nsu', 15)->default('0');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sefaz_sync_states');
    }
};
