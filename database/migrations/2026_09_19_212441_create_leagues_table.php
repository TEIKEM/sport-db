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
      Schema::create('leagues', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('external_id')->nullable();
    $table->string('name');
    $table->string('country')->nullable();
    $table->string('code', 10)->nullable();
    $table->timestamps();
    $table->unique(['sport_id', 'external_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
