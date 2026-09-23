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
        Schema::create('seasons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('league_id')->constrained()->cascadeOnDelete();
    $table->string('label', 20);          // ex : 2025/2026
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->timestamps();
    $table->unique(['league_id', 'label']);
});
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
