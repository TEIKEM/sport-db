<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fusionne une équipe en double avec l'équipe à garder : déplace tous les
 * matchs, joueurs, stats et saisons vers l'équipe conservée, puis supprime
 * le doublon. Tout se fait dans une transaction : soit tout réussit, soit
 * rien n'est modifié.
 *
 * Utilisation :
 *   php artisan teams:merge {id_a_garder} {id_du_doublon}
 */
class MergeTeams extends Command
{
    protected $signature = 'teams:merge {keep : Identifiant de l\'équipe à garder} {duplicate : Identifiant du doublon à supprimer}';

    protected $description = 'Fusionne deux fiches équipe en double (déplace tout vers l\'équipe conservée)';

    public function handle(): int
    {
        $keepId = (int) $this->argument('keep');
        $dupId = (int) $this->argument('duplicate');

        if ($keepId === $dupId) {
            $this->error('Les deux identifiants sont identiques.');

            return self::FAILURE;
        }

        $keep = Team::find($keepId);
        $dup = Team::find($dupId);

        if (! $keep || ! $dup) {
            $this->error('Une des deux équipes est introuvable. Vérifie les identifiants.');

            return self::FAILURE;
        }

        $this->info("À garder : #{$keep->id} {$keep->name}");
        $this->info("À supprimer : #{$dup->id} {$dup->name}");

        if (! $this->confirm('Confirmer la fusion ?', true)) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($keep, $dup) {
            // Saisons : associer l'équipe conservée là où le doublon était associé,
            // sans dupliquer si elle l'était déjà des deux côtés
            $seasonIds = DB::table('season_team')->where('team_id', $dup->id)->pluck('season_id');

            foreach ($seasonIds as $seasonId) {
                DB::table('season_team')->insertOrIgnore([
                    'season_id' => $seasonId,
                    'team_id' => $keep->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('season_team')->where('team_id', $dup->id)->delete();

            // Matchs
            DB::table('fixtures')->where('home_team_id', $dup->id)->update(['home_team_id' => $keep->id]);
            DB::table('fixtures')->where('away_team_id', $dup->id)->update(['away_team_id' => $keep->id]);

            // Joueurs
            DB::table('players')->where('team_id', $dup->id)->update(['team_id' => $keep->id]);

            // Stats de match : si l'équipe conservée a déjà une ligne de stats pour ce
            // match (cas rare), on garde la sienne et on supprime celle du doublon
            $conflicts = DB::table('fixture_stats as a')
                ->join('fixture_stats as b', function ($j) use ($keep, $dup) {
                    $j->on('a.fixture_id', '=', 'b.fixture_id')
                        ->where('a.team_id', $dup->id)
                        ->where('b.team_id', $keep->id);
                })
                ->pluck('a.id');

            DB::table('fixture_stats')->whereIn('id', $conflicts)->delete();
            DB::table('fixture_stats')->where('team_id', $dup->id)->update(['team_id' => $keep->id]);

            // Événements (buts, cartons...)
            DB::table('fixture_events')->where('team_id', $dup->id)->update(['team_id' => $keep->id]);

            // On garde l'identifiant API du doublon si l'équipe conservée n'en a pas
            if (! $keep->external_id && $dup->external_id) {
                $keep->update(['external_id' => $dup->external_id]);
            }

            $dup->delete();
        });

        $this->info('Fusion terminée.');

        return self::SUCCESS;
    }
}
