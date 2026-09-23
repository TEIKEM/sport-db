<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW team_matches AS
            SELECT
                f.id AS fixture_id, f.league_id, f.season_id, f.kickoff_at,
                f.home_team_id AS team_id, f.away_team_id AS opponent_id,
                1 AS is_home,
                CAST(f.home_score AS SIGNED) AS goals_for,
                CAST(f.away_score AS SIGNED) AS goals_against,
                CAST(f.ht_home_score AS SIGNED) AS ht_for,
                CAST(f.ht_away_score AS SIGNED) AS ht_against,
                (CAST(f.home_score AS SIGNED) - CAST(f.ht_home_score AS SIGNED)) AS h2_for,
                (CAST(f.away_score AS SIGNED) - CAST(f.ht_away_score AS SIGNED)) AS h2_against,
                (CAST(f.home_score AS SIGNED) + CAST(f.away_score AS SIGNED)) AS total_goals,
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
                CAST(f.away_score AS SIGNED),
                CAST(f.home_score AS SIGNED),
                CAST(f.ht_away_score AS SIGNED),
                CAST(f.ht_home_score AS SIGNED),
                (CAST(f.away_score AS SIGNED) - CAST(f.ht_away_score AS SIGNED)),
                (CAST(f.home_score AS SIGNED) - CAST(f.ht_home_score AS SIGNED)),
                (CAST(f.home_score AS SIGNED) + CAST(f.away_score AS SIGNED)),
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
    }

    public function down(): void
    {
        // Pas de retour en arrière : l'ancienne version de la vue était incorrecte.
    }
};
