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
        Schema::create('person_person_category', function (Blueprint $table) {
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['person_id', 'person_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_person_category');
    }
};
