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
        Schema::create('sefaz_certificates', function (Blueprint $table) {
            $table->id();
            $table->longText('pfx');
            $table->text('password');
            $table->string('subject');
            $table->string('cnpj', 14)->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sefaz_certificates');
    }
};
