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
  Schema::create('fixture_stats', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
    $table->foreignId('team_id')->constrained();
    $table->unsignedTinyInteger('possession')->nullable();
    $table->unsignedTinyInteger('shots')->nullable();
    $table->unsignedTinyInteger('shots_on_target')->nullable();
    $table->unsignedTinyInteger('corners')->nullable();
    $table->unsignedTinyInteger('fouls')->nullable();
    $table->unsignedTinyInteger('yellow_cards')->nullable();
    $table->unsignedTinyInteger('red_cards')->nullable();
    $table->decimal('xg', 4, 2)->nullable();
    $table->timestamps();
    $table->unique(['fixture_id', 'team_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_stats');
    }
};
