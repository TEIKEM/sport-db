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
      Schema::create('fixtures', function (Blueprint $table) {
    $table->id();
    $table->foreignId('league_id')->constrained()->cascadeOnDelete();
    $table->foreignId('season_id')->constrained()->cascadeOnDelete();
    $table->string('source', 30)->default('manual');   // football-data, csv, manual...
    $table->unsignedBigInteger('external_id')->nullable();
    $table->foreignId('home_team_id')->constrained('teams');
    $table->foreignId('away_team_id')->constrained('teams');
    $table->dateTime('kickoff_at')->index();
    $table->string('status', 20)->default('SCHEDULED')->index();
    $table->unsignedTinyInteger('home_score')->nullable();
    $table->unsignedTinyInteger('away_score')->nullable();
    $table->unsignedTinyInteger('ht_home_score')->nullable();
    $table->unsignedTinyInteger('ht_away_score')->nullable();
    $table->timestamps();
    $table->unique(['source', 'external_id']);
    $table->index(['league_id', 'kickoff_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
