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
      Schema::create('odds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
    $table->string('bookmaker', 50);
    $table->string('market', 30);       // 1X2, OU2.5, BTTS...
    $table->string('selection', 30);    // HOME, DRAW, AWAY, OVER, UNDER...
    $table->decimal('price', 8, 3);
    $table->dateTime('recorded_at');
    $table->index(['fixture_id', 'market', 'recorded_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};
