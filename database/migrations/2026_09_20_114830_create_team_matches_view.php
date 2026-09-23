<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
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
                CASE WHEN f.home_score > 0 AND f.away_score > 0 THEN 1 ELSE 0 END AS btts
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
                CASE WHEN f.home_score > 0 AND f.away_score > 0 THEN 1 ELSE 0 END
            FROM fixtures f
            WHERE f.status = 'FINISHED'
              AND f.home_score IS NOT NULL AND f.away_score IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS team_matches');
    }
};
