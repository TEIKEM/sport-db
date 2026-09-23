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
      Schema::create('trades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
    $table->string('market', 30);
    $table->string('selection', 30);
    $table->decimal('stake', 10, 2);
    $table->decimal('odds_in', 8, 3);
    $table->decimal('odds_out', 8, 3)->nullable();
    $table->string('result', 10)->default('OPEN');   // OPEN, WIN, LOSS, VOID
    $table->decimal('profit', 10, 2)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
