<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Type de compétition : LEAGUE, CUP, CONTINENTAL_CLUB, INTERNATIONAL
        Schema::table('leagues', function (Blueprint $table) {
            $table->string('type', 20)->default('LEAGUE');
        });

        // Type d'équipe : CLUB ou NATIONAL
        Schema::table('teams', function (Blueprint $table) {
            $table->string('type', 10)->default('CLUB');
        });

        // Infos de phase, groupe, prolongations et tirs au but
        Schema::table('fixtures', function (Blueprint $table) {
            $table->string('stage', 30)->nullable();
            $table->string('group_name', 10)->nullable();
            $table->boolean('is_neutral')->default(false);
            $table->string('decided_by', 15)->nullable();
            $table->unsignedTinyInteger('et_home_score')->nullable();
            $table->unsignedTinyInteger('et_away_score')->nullable();
            $table->unsignedTinyInteger('pen_home_score')->nullable();
            $table->unsignedTinyInteger('pen_away_score')->nullable();
            $table->index(['season_id', 'stage', 'group_name']);
        });

        // On refait la vue team_matches avec les nouvelles colonnes à la fin
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW team_matches AS
            SELECT
                f.id AS fixture_id, f.league_id, f.season_id, f.kickoff_at,
                f.home_team_id AS team_id, f.away_team_id AS opponent_id,
                1 AS is_home,
                f.home_score AS goals_for, f.away_score AS goals_against,
                f.ht_home_score AS ht_for, f.ht_away_score AS ht_against,
                (f.home_score - f.ht_home_score) AS h2_for,
                (f.away_score - f.ht_away_score) AS h2_against,
                (f.home_score + f.away_score) AS total_goals,
                CASE WHEN f.home_score > f.away_score THEN 'W'
                     WHEN f.home_score = f.away_score THEN 'D' ELSE 'L' END AS result,
                CASE WHEN f.home_score > f.away_score THEN 3
                     WHEN f.home_score = f.away_score THEN 1 ELSE 0 END AS points,
                CASE WHEN f.home_score > 0 AND f.away_score > 0 THEN 1 ELSE 0 END AS btts,
                f.stage AS stage, f.group_name AS group_name, f.is_neutral AS is_neutral
            FROM fixtures f
            WHERE f.status = 'FINISHED'
              AND f.home_score IS NOT NULL AND f.away_score IS NOT NULL

            UNION ALL

            SELECT
                f.id, f.league_id, f.season_id, f.kickoff_at,
                f.away_team_id, f.home_team_id,
                0,
                f.away_score, f.home_score,
                f.ht_away_score, f.ht_home_score,
                (f.away_score - f.ht_away_score),
                (f.home_score - f.ht_home_score),
                (f.home_score + f.away_score),
                CASE WHEN f.away_score > f.home_score THEN 'W'
                     WHEN f.away_score = f.home_score THEN 'D' ELSE 'L' END,
                CASE WHEN f.away_score > f.home_score THEN 3
                     WHEN f.away_score = f.home_score THEN 1 ELSE 0 END,
                CASE WHEN f.home_score > 0 AND f.away_score > 0 THEN 1 ELSE 0 END,
                f.stage, f.group_name, f.is_neutral
            FROM fixtures f
            WHERE f.status = 'FINISHED'
              AND f.home_score IS NOT NULL AND f.away_score IS NOT NULL
        SQL);

        // Classements de toutes les compétitions (par groupe si groupes)
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW standings AS
            SELECT
                x.league_id, x.season_id, x.group_name, x.team_id,
                t.name AS team_name,
                x.played, x.wins, x.draws, x.losses,
                x.goals_for, x.goals_against, x.goal_diff, x.points,
                RANK() OVER (
                    PARTITION BY x.league_id, x.season_id, x.group_name
                    ORDER BY x.points DESC, x.goal_diff DESC, x.goals_for DESC
                ) AS pos
            FROM (
                SELECT
                    tm.league_id, tm.season_id, tm.group_name, tm.team_id,
                    COUNT(*) AS played,
                    SUM(tm.result = 'W') AS wins,
                    SUM(tm.result = 'D') AS draws,
                    SUM(tm.result = 'L') AS losses,
                    SUM(tm.goals_for) AS goals_for,
                    SUM(tm.goals_against) AS goals_against,
                    SUM(tm.goals_for - tm.goals_against) AS goal_diff,
                    SUM(tm.points) AS points
                FROM team_matches tm
                WHERE tm.stage IS NULL
                   OR tm.stage IN ('REGULAR_SEASON', 'GROUP_STAGE', 'LEAGUE_PHASE')
                GROUP BY tm.league_id, tm.season_id, tm.group_name, tm.team_id
            ) x
            JOIN teams t ON t.id = x.team_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS standings');

        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropIndex(['season_id', 'stage', 'group_name']);
            $table->dropColumn([
                'stage', 'group_name', 'is_neutral', 'decided_by',
                'et_home_score', 'et_away_score', 'pen_home_score', 'pen_away_score',
            ]);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
