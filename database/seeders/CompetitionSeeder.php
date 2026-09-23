<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $football = Sport::firstOrCreate(['name' => 'Football'], ['slug' => 'football']);

        // [nom, pays ou zone, code, type]
        $competitions = [
            // Championnats nationaux
            ['Premier League', 'Angleterre', 'PL', 'LEAGUE'],
            ['Championship', 'Angleterre', 'ELC', 'LEAGUE'],
            ['La Liga', 'Espagne', 'PD', 'LEAGUE'],
            ['Segunda División', 'Espagne', 'SD', 'LEAGUE'],
            ['Serie A', 'Italie', 'SA', 'LEAGUE'],
            ['Serie B', 'Italie', 'SB', 'LEAGUE'],
            ['Bundesliga', 'Allemagne', 'BL1', 'LEAGUE'],
            ['2. Bundesliga', 'Allemagne', 'BL2', 'LEAGUE'],
            ['Ligue 1', 'France', 'FL1', 'LEAGUE'],
            ['Ligue 2', 'France', 'FL2', 'LEAGUE'],
            ['Eredivisie', 'Pays-Bas', 'DED', 'LEAGUE'],
            ['Primeira Liga', 'Portugal', 'PPL', 'LEAGUE'],
            ['Jupiler Pro League', 'Belgique', 'BEL', 'LEAGUE'],
            ['Scottish Premiership', 'Écosse', 'SCO', 'LEAGUE'],
            ['Süper Lig', 'Turquie', 'TUR', 'LEAGUE'],
            ['Major League Soccer', 'États-Unis', 'MLS', 'LEAGUE'],
            ['Brasileirão Série A', 'Brésil', 'BSA', 'LEAGUE'],
            ['Liga Profesional', 'Argentine', 'ARG', 'LEAGUE'],
            ['Saudi Pro League', 'Arabie saoudite', 'KSA', 'LEAGUE'],
            ['Elite One', 'Cameroun', 'CMR', 'LEAGUE'],
            ['Botola Pro', 'Maroc', 'MAR', 'LEAGUE'],
            ['Egyptian Premier League', 'Égypte', 'EGY', 'LEAGUE'],
            ['South African Premiership', 'Afrique du Sud', 'RSA', 'LEAGUE'],

            // Coupes nationales
            ['FA Cup', 'Angleterre', 'FAC', 'CUP'],
            ['EFL Cup', 'Angleterre', 'EFL', 'CUP'],
            ['Copa del Rey', 'Espagne', 'CDR', 'CUP'],
            ['Coppa Italia', 'Italie', 'CIT', 'CUP'],
            ['DFB-Pokal', 'Allemagne', 'DFB', 'CUP'],
            ['Coupe de France', 'France', 'CDF', 'CUP'],

            // Compétitions de clubs continentales et mondiales
            ['UEFA Champions League', 'Europe', 'CL', 'CONTINENTAL_CLUB'],
            ['UEFA Europa League', 'Europe', 'EL', 'CONTINENTAL_CLUB'],
            ['UEFA Conference League', 'Europe', 'ECL', 'CONTINENTAL_CLUB'],
            ['UEFA Super Cup', 'Europe', 'USC', 'CONTINENTAL_CLUB'],
            ['CAF Champions League', 'Afrique', 'CAFCL', 'CONTINENTAL_CLUB'],
            ['CAF Confederation Cup', 'Afrique', 'CAFCC', 'CONTINENTAL_CLUB'],
            ['Copa Libertadores', 'Amérique du Sud', 'CLI', 'CONTINENTAL_CLUB'],
            ['Copa Sudamericana', 'Amérique du Sud', 'CSU', 'CONTINENTAL_CLUB'],
            ['AFC Champions League Elite', 'Asie', 'ACL', 'CONTINENTAL_CLUB'],
            ['FIFA Club World Cup', 'Monde', 'CWC', 'CONTINENTAL_CLUB'],

            // Compétitions de sélections nationales
            ['FIFA World Cup', 'Monde', 'WC', 'INTERNATIONAL'],
            ['World Cup Qualifiers', 'Monde', 'WCQ', 'INTERNATIONAL'],
            ['UEFA European Championship', 'Europe', 'EC', 'INTERNATIONAL'],
            ['European Championship Qualifiers', 'Europe', 'ECQ', 'INTERNATIONAL'],
            ['UEFA Nations League', 'Europe', 'UNL', 'INTERNATIONAL'],
            ['Copa América', 'Amérique du Sud', 'COPA', 'INTERNATIONAL'],
            ['Africa Cup of Nations', 'Afrique', 'AFCON', 'INTERNATIONAL'],
            ['Africa Cup of Nations Qualifiers', 'Afrique', 'AFCONQ', 'INTERNATIONAL'],
            ['African Nations Championship', 'Afrique', 'CHAN', 'INTERNATIONAL'],
            ['AFC Asian Cup', 'Asie', 'ASIA', 'INTERNATIONAL'],
            ['CONCACAF Gold Cup', 'Amérique du Nord', 'GOLD', 'INTERNATIONAL'],
            ['Olympic Football Tournament', 'Monde', 'OLY', 'INTERNATIONAL'],
            ['International Friendlies', 'Monde', 'FRI', 'INTERNATIONAL'],
        ];

        foreach ($competitions as [$name, $country, $code, $type]) {
            League::firstOrCreate(
                ['sport_id' => $football->id, 'name' => $name],
                ['country' => $country, 'code' => $code, 'type' => $type]
            );
        }
    }
}
