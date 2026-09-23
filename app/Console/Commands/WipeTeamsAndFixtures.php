<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vide les équipes, matchs, stats, joueurs, cotes et paris — en gardant les
 * compétitions (leagues) et les saisons (seasons). Utile pour repartir sur
 * un import propre depuis l'API, sans recréer les compétitions à la main.
 *
 * Utilisation : php artisan db:wipe-teams
 */
class WipeTeamsAndFixtures extends Command
{
    protected $signature = 'db:wipe-teams';

    protected $description = 'Vide les équipes, matchs, stats, joueurs, cotes et paris (garde les compétitions et saisons)';

    private const TABLES = [
        'trades', 'odds', 'fixture_events', 'player_stats',
        'fixture_stats', 'fixtures', 'season_team', 'players', 'teams',
    ];

    public function handle(): int
    {
        $this->warn('Cette commande va vider : ' . implode(', ', self::TABLES));
        $this->line('Les compétitions (leagues) et les saisons (seasons) seront conservées.');

        if (! $this->confirm('Confirmer ?', false)) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
            $this->line("Vidé : {$table}");
        }

        Schema::enableForeignKeyConstraints();

        $this->info('Terminé. Tu peux relancer : php artisan matches:import --all');

        return self::SUCCESS;
    }
}
