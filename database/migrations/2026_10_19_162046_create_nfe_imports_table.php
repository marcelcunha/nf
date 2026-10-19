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
        Schema::create('nfe_imports', function (Blueprint $table) {
            $table->id();
            $table->string('access_key', 44)->unique();
            $table->foreignId('supplier_id')->constrained('people')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedSmallInteger('series');
            $table->dateTime('issued_at');
            $table->decimal('total', 15, 2);
            $table->longText('xml');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfe_imports');
    }
};
