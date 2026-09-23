<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Arbitres
        Schema::create('referees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->timestamps();
        });

        // Joueurs
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('position', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();
        });

        // Infos supplémentaires sur les matchs
        Schema::table('fixtures', function (Blueprint $table) {
            $table->foreignId('referee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('venue')->nullable();
            $table->unsignedTinyInteger('matchday')->nullable();
            $table->string('weather', 50)->nullable();
            $table->unsignedInteger('attendance')->nullable();
            $table->text('notes')->nullable();
        });

        // Paris simulés (true) ou réels (false) : simulé par défaut
        Schema::table('trades', function (Blueprint $table) {
            $table->boolean('is_simulated')->default(true)->after('fixture_id');
        });

        // Stats par joueur et par match
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_starter')->default(false);
            $table->unsignedTinyInteger('minutes')->nullable();
            $table->unsignedTinyInteger('goals')->nullable();
            $table->unsignedTinyInteger('assists')->nullable();
            $table->unsignedTinyInteger('shots')->nullable();
            $table->unsignedTinyInteger('shots_on_target')->nullable();
            $table->unsignedTinyInteger('yellow_cards')->nullable();
            $table->unsignedTinyInteger('red_cards')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->timestamps();
            $table->unique(['fixture_id', 'player_id']);
        });

        // Événements minute par minute (buts, cartons, remplacements...)
        Schema::create('fixture_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('minute');
            $table->string('type', 20);   // GOAL, YELLOW, RED, SUB, PENALTY...
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['fixture_id', 'minute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixture_events');
        Schema::dropIfExists('player_stats');

        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn('is_simulated');
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referee_id');
            $table->dropColumn(['venue', 'matchday', 'weather', 'attendance', 'notes']);
        });

        Schema::dropIfExists('players');
        Schema::dropIfExists('referees');
    }
};
