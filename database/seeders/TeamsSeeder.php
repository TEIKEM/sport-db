<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Équipes de la saison 2026/2027 :
 *  - 7 championnats : Angleterre, Espagne, Italie, Allemagne, France, Pays-Bas, Portugal
 *  - UEFA Champions League (les 36 clubs de la phase de ligue)
 *
 * Le seeder :
 *  1. crée le sport Football et les compétitions si elles n'existent pas
 *  2. crée la saison 2026/2027 de chaque compétition (si elle n'existe pas déjà)
 *  3. crée chaque équipe UNE seule fois (même si elle joue plusieurs compétitions)
 *  4. associe les équipes à la saison (table season_team)
 *
 * Il ne crée jamais de doublons : tu peux le relancer sans risque.
 */
class TeamsSeeder extends Seeder
{
    public function run(): void
    {
        $football = Sport::firstOrCreate(['name' => 'Football'], ['slug' => 'football']);

        // ---------------------------------------------------------------
        // 1. Les 7 championnats 2026/2027
        // ---------------------------------------------------------------
        $domestic = [
            ['Premier League', 'Angleterre', 'PL', [
                'Arsenal', 'Aston Villa', 'Bournemouth', 'Brentford', 'Brighton',
                'Chelsea', 'Coventry City', 'Crystal Palace', 'Everton', 'Fulham',
                'Hull City', 'Ipswich Town', 'Leeds United', 'Liverpool', 'Manchester City',
                'Manchester United', 'Newcastle United', 'Nottingham Forest', 'Sunderland', 'Tottenham Hotspur',
            ]],
            ['La Liga', 'Espagne', 'PD', [
                'Alavés', 'Athletic Club', 'Atlético Madrid', 'Barcelona', 'Celta Vigo',
                'Deportivo La Coruña', 'Elche', 'Espanyol', 'Getafe', 'Levante',
                'Málaga', 'Osasuna', 'Racing Santander', 'Rayo Vallecano', 'Real Betis',
                'Real Madrid', 'Real Sociedad', 'Sevilla', 'Valencia', 'Villarreal',
            ]],
            ['Serie A', 'Italie', 'SA', [
                'Atalanta', 'Bologna', 'Cagliari', 'Como', 'Fiorentina',
                'Frosinone', 'Genoa', 'Inter Milan', 'Juventus', 'Lazio',
                'Lecce', 'AC Milan', 'Monza', 'Napoli', 'Parma',
                'Roma', 'Sassuolo', 'Torino', 'Udinese', 'Venezia',
            ]],
            ['Bundesliga', 'Allemagne', 'BL1', [
                'Bayern Munich', 'Borussia Dortmund', 'RB Leipzig', 'VfB Stuttgart', 'Hoffenheim',
                'Bayer Leverkusen', 'Freiburg', 'Eintracht Frankfurt', 'Augsburg', 'Mainz 05',
                'Union Berlin', 'Borussia Mönchengladbach', 'Hamburger SV', 'FC Cologne', 'Werder Bremen',
                'Schalke 04', 'Elversberg', 'Paderborn',
            ]],
            ['Ligue 1', 'France', 'FL1', [
                'Angers', 'Auxerre', 'Brest', 'Le Havre', 'Lens',
                'Lille', 'Lorient', 'Lyon', 'Marseille', 'Monaco',
                'Nice', 'Paris FC', 'Paris Saint-Germain', 'Rennes', 'Strasbourg',
                'Toulouse', 'Troyes', 'Le Mans',
            ]],
            ['Eredivisie', 'Pays-Bas', 'DED', [
                'ADO Den Haag', 'Ajax', 'AZ Alkmaar', 'Cambuur', 'Excelsior',
                'Feyenoord', 'Fortuna Sittard', 'Go Ahead Eagles', 'Groningen', 'Heerenveen',
                'NEC Nijmegen', 'PEC Zwolle', 'PSV Eindhoven', 'Sparta Rotterdam', 'Telstar',
                'Twente', 'Utrecht', 'Willem II',
            ]],
            ['Primeira Liga', 'Portugal', 'PPL', [
                'Académico de Viseu', 'Alverca', 'Arouca', 'Benfica', 'Braga',
                'Casa Pia', 'Estoril Praia', 'Estrela da Amadora', 'Famalicão', 'Gil Vicente',
                'Vitória Guimarães', 'Marítimo', 'Moreirense', 'Nacional', 'Porto',
                'Rio Ave', 'Santa Clara', 'Sporting CP',
            ]],
        ];

        foreach ($domestic as [$name, $country, $code, $teams]) {
            $league = $this->league($football, $name, $country, $code, 'LEAGUE');

            $this->fillSeason(
                $football,
                $league,
                array_fill_keys($teams, $country),
                '2026-08-01',
                '2027-06-30'
            );
        }

        // ---------------------------------------------------------------
        // 2. UEFA Champions League 2026/2027 : phase de ligue (36 clubs)
        // ---------------------------------------------------------------
        $champions = $this->league($football, 'UEFA Champions League', 'Europe', 'CL', 'CONTINENTAL_CLUB');

        $championsTeams = [
            // Angleterre
            'Arsenal' => 'Angleterre',
            'Aston Villa' => 'Angleterre',
            'Liverpool' => 'Angleterre',
            'Manchester City' => 'Angleterre',
            'Manchester United' => 'Angleterre',
            // Espagne
            'Atlético Madrid' => 'Espagne',
            'Barcelona' => 'Espagne',
            'Real Betis' => 'Espagne',
            'Real Madrid' => 'Espagne',
            'Villarreal' => 'Espagne',
            // Italie
            'Como' => 'Italie',
            'Inter Milan' => 'Italie',
            'Napoli' => 'Italie',
            'Roma' => 'Italie',
            // Allemagne
            'Bayern Munich' => 'Allemagne',
            'Borussia Dortmund' => 'Allemagne',
            'RB Leipzig' => 'Allemagne',
            'VfB Stuttgart' => 'Allemagne',
            // France
            'Lens' => 'France',
            'Lille' => 'France',
            'Paris Saint-Germain' => 'France',
            // Pays-Bas
            'Feyenoord' => 'Pays-Bas',
            'PSV Eindhoven' => 'Pays-Bas',
            // Portugal
            'Porto' => 'Portugal',
            'Sporting CP' => 'Portugal',
            // Autres pays
            'AEK Athens' => 'Grèce',
            'Bodø/Glimt' => 'Norvège',
            'Club Brugge' => 'Belgique',
            'Fenerbahçe' => 'Turquie',
            'Galatasaray' => 'Turquie',
            'LASK' => 'Autriche',
            'Sabah' => 'Azerbaïdjan',
            'Shakhtar Donetsk' => 'Ukraine',
            'Slavia Prague' => 'République tchèque',
            'Slovan Bratislava' => 'Slovaquie',
            'Viking' => 'Norvège',
        ];

        $this->fillSeason($football, $champions, $championsTeams, '2026-07-01', '2027-06-05');
    }

    /**
     * Crée la compétition si elle n'existe pas (même sport, même nom).
     */
    private function league(Sport $sport, string $name, string $country, string $code, string $type): League
    {
        $attributes = ['country' => $country, 'code' => $code];

        if (Schema::hasColumn('leagues', 'type')) {
            $attributes['type'] = $type;
        }

        return League::firstOrCreate(
            ['sport_id' => $sport->id, 'name' => $name],
            $attributes
        );
    }

    /**
     * Crée (ou retrouve) la saison 2026/2027, crée les équipes et les associe à la saison.
     *
     * @param  array<string, string>  $teams  nom de l'équipe => pays
     */
    private function fillSeason(Sport $sport, League $league, array $teams, string $start, string $end): void
    {
        // Si tu as déjà créé la saison à la main, on la réutilise au lieu de la dupliquer
        $season = Season::where('league_id', $league->id)
            ->whereIn('label', ['2026/2027', '2026-2027', 'saison 2026-2027'])
            ->first();

        if (! $season) {
            $season = Season::create([
                'league_id' => $league->id,
                'label' => '2026/2027',
                'start_date' => $start,
                'end_date' => $end,
            ]);
        }

        $teamHasType = Schema::hasColumn('teams', 'type');

        foreach ($teams as $name => $country) {
            $attributes = ['country' => $country];

            if ($teamHasType) {
                $attributes['type'] = 'CLUB';
            }

            $team = Team::firstOrCreate(
                ['sport_id' => $sport->id, 'name' => $name],
                $attributes
            );

            // Association équipe <-> saison (sans doublon)
            DB::table('season_team')->insertOrIgnore([
                'season_id' => $season->id,
                'team_id' => $team->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
