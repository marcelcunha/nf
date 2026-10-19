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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('people')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('ean', 14)->nullable();
            $table->string('ncm', 8)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('unit', 6)->nullable();
            $table->decimal('last_unit_cost', 15, 4)->nullable();
            $table->timestamps();
            $table->unique(['supplier_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
