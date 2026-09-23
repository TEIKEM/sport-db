<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Stats complètes par équipe, ligue et saison
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW team_season_stats AS
            SELECT
                tm.team_id, tm.league_id, tm.season_id,
                COUNT(*) AS played,

                -- Résultats
                SUM(tm.result = 'W') AS wins,
                SUM(tm.result = 'D') AS draws,
                SUM(tm.result = 'L') AS losses,
                SUM(tm.points) AS points,

                -- Buts
                SUM(tm.goals_for) AS goals_for,
                SUM(tm.goals_against) AS goals_against,
                SUM(tm.goals_for - tm.goals_against) AS goal_diff,
                ROUND(AVG(tm.goals_for), 2) AS avg_goals_for,
                ROUND(AVG(tm.goals_against), 2) AS avg_goals_against,
                ROUND(AVG(tm.total_goals), 2) AS avg_total_goals,
                SUM(tm.goals_against = 0) AS clean_sheets,
                SUM(tm.goals_for = 0) AS failed_to_score,

                -- Over / Under (total des deux équipes)
                SUM(tm.total_goals > 0.5) AS over_0_5,
                SUM(tm.total_goals > 1.5) AS over_1_5,
                SUM(tm.total_goals > 2.5) AS over_2_5,
                SUM(tm.total_goals > 3.5) AS over_3_5,
                SUM(tm.total_goals > 4.5) AS over_4_5,
                SUM(tm.total_goals < 1.5) AS under_1_5,
                SUM(tm.total_goals < 2.5) AS under_2_5,
                SUM(tm.total_goals < 3.5) AS under_3_5,
                SUM(tm.btts) AS btts,

                -- Over d'équipe (buts de l'équipe seule)
                SUM(tm.goals_for > 0.5) AS team_over_0_5,
                SUM(tm.goals_for > 1.5) AS team_over_1_5,
                SUM(tm.goals_for > 2.5) AS team_over_2_5,

                -- 1re mi-temps
                SUM(tm.ht_for) AS h1_goals_for,
                SUM(tm.ht_against) AS h1_goals_against,
                ROUND(AVG(tm.ht_for), 2) AS avg_h1_for,
                ROUND(AVG(tm.ht_against), 2) AS avg_h1_against,
                SUM(tm.ht_for + tm.ht_against > 0.5) AS h1_over_0_5,
                SUM(tm.ht_for + tm.ht_against > 1.5) AS h1_over_1_5,
                SUM(tm.ht_for > tm.ht_against) AS leading_at_ht,
                SUM(tm.ht_for = tm.ht_against) AS drawing_at_ht,
                SUM(tm.ht_for < tm.ht_against) AS trailing_at_ht,

                -- 2e mi-temps
                SUM(tm.h2_for) AS h2_goals_for,
                SUM(tm.h2_against) AS h2_goals_against,
                ROUND(AVG(tm.h2_for), 2) AS avg_h2_for,
                ROUND(AVG(tm.h2_against), 2) AS avg_h2_against,
                SUM(tm.h2_for + tm.h2_against > 0.5) AS h2_over_0_5,
                SUM(tm.h2_for + tm.h2_against > 1.5) AS h2_over_1_5,
                SUM(tm.h2_against > 0) AS conceded_in_h2,

                -- Remontées et matchs perdus après avoir mené
                SUM(tm.ht_for < tm.ht_against AND tm.result = 'W') AS comebacks,
                SUM(tm.ht_for > tm.ht_against AND tm.result <> 'W') AS dropped_points_after_leading,

                -- Domicile
                SUM(tm.is_home = 1) AS home_played,
                SUM(tm.is_home = 1 AND tm.result = 'W') AS home_wins,
                SUM(tm.is_home = 1 AND tm.result = 'D') AS home_draws,
                SUM(tm.is_home = 1 AND tm.result = 'L') AS home_losses,

                -- Extérieur
                SUM(tm.is_home = 0) AS away_played,
                SUM(tm.is_home = 0 AND tm.result = 'W') AS away_wins,
                SUM(tm.is_home = 0 AND tm.result = 'D') AS away_draws,
                SUM(tm.is_home = 0 AND tm.result = 'L') AS away_losses
            FROM team_matches tm
            GROUP BY tm.team_id, tm.league_id, tm.season_id
        SQL);

        // Moyennes des stats de match saisies (tirs, corners, cartons...)
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW team_stat_averages AS
            SELECT
                fs.team_id, f.league_id, f.season_id,
                COUNT(*) AS matches_with_stats,
                ROUND(AVG(fs.possession), 1) AS avg_possession,
                ROUND(AVG(fs.shots), 1) AS avg_shots,
                ROUND(AVG(fs.shots_on_target), 1) AS avg_shots_on_target,
                ROUND(AVG(fs.corners), 1) AS avg_corners,
                ROUND(AVG(fs.fouls), 1) AS avg_fouls,
                ROUND(AVG(fs.yellow_cards), 2) AS avg_yellow_cards,
                ROUND(AVG(fs.red_cards), 2) AS avg_red_cards,
                ROUND(AVG(fs.xg), 2) AS avg_xg
            FROM fixture_stats fs
            JOIN fixtures f ON f.id = fs.fixture_id
            WHERE f.status = 'FINISHED'
            GROUP BY fs.team_id, f.league_id, f.season_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS team_stat_averages');
        DB::statement('DROP VIEW IF EXISTS team_season_stats');
    }
};
