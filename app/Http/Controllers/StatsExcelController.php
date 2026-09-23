<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\League;
use App\Support\FixtureStatSaver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StatsExcelController extends Controller
{
    /** Colonnes de stats, dans l'ordre où elles apparaissent dans le fichier (préfixées dom_/ext_). */
    private const FIELDS = [
        'possession' => 'possession',
        'shots' => 'tirs',
        'shots_on_target' => 'tirs_cadres',
        'corners' => 'corners',
        'fouls' => 'fautes',
        'yellow_cards' => 'jaunes',
        'red_cards' => 'rouges',
        'xg' => 'xg',
    ];

    /**
     * Page : compte les matchs sans stats, propose les filtres et les deux formulaires (export/import).
     */
    public function index(Request $request)
    {
        $leagues = League::orderBy('name')->get();

        $query = Fixture::where('status', 'FINISHED')->whereDoesntHave('stats');

        if ($request->filled('league_id')) {
            $query->where('league_id', $request->integer('league_id'));
        }

        $count = $query->count();

        return view('stats-excel', [
            'leagues' => $leagues,
            'count' => $count,
            'leagueId' => $request->integer('league_id') ?: null,
        ]);
    }

    /**
     * Génère et envoie le fichier Excel des matchs sans stats.
     */
    public function export(Request $request)
    {
        $fixtures = Fixture::with(['league', 'homeTeam', 'awayTeam'])
            ->where('status', 'FINISHED')
            ->whereDoesntHave('stats')
            ->when($request->filled('league_id'), fn ($q) => $q->where('league_id', $request->integer('league_id')))
            ->orderBy('kickoff_at')
            ->limit(500) // au-delà, exporte par compétition pour rester lisible
            ->get();

        if ($fixtures->isEmpty()) {
            return back()->with('error', 'Aucun match sans stats pour cette sélection.');
        }

        $headers = ['fixture_id', 'date', 'competition', 'domicile', 'exterieur'];

        foreach (self::FIELDS as $suffix) {
            $headers[] = 'dom_' . $suffix;
        }
        foreach (self::FIELDS as $suffix) {
            $headers[] = 'ext_' . $suffix;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stats à remplir');
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($fixtures as $fixture) {
            $sheet->fromArray([
                $fixture->id,
                Carbon::parse($fixture->kickoff_at)->format('d/m/Y'),
                $fixture->league->name,
                $fixture->homeTeam->name,
                $fixture->awayTeam->name,
            ], null, "A{$row}");
            $row++;
        }

        // Mise en forme : en-tête grisé, colonnes techniques (fixture_id) légèrement grisées pour dire "ne pas modifier"
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E2F3');
        $sheet->getStyle("A1:A{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF999999'));
        $sheet->freezePane('F2');

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'stats-a-remplir-' . now()->format('Y-m-d') . '.xlsx';
        $tmpPath = storage_path('app/' . $filename);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Relit le fichier Excel rempli et enregistre les stats de chaque match.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $header = array_map('trim', $rows[0] ?? []);
        $colIndex = array_flip($header); // nom de colonne => index (base 0)

        $updated = 0;
        $skipped = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $lineNumber = $i + 2; // ligne réelle dans Excel (1 = en-tête)
            $fixtureId = $row[$colIndex['fixture_id'] ?? -1] ?? null;

            if (! $fixtureId) {
                continue; // ligne vide, on ignore silencieusement
            }

            $fixture = Fixture::find($fixtureId);

            if (! $fixture) {
                $skipped[] = "Ligne {$lineNumber} : match #{$fixtureId} introuvable (identifiant modifié ?)";

                continue;
            }

            $data = ['fixture_id' => $fixture->id, 'home' => [], 'away' => []];
            $hasAnyValue = false;

            foreach (self::FIELDS as $field => $suffix) {
                $homeVal = $row[$colIndex['dom_' . $suffix] ?? -1] ?? null;
                $awayVal = $row[$colIndex['ext_' . $suffix] ?? -1] ?? null;

                $data['home'][$field] = ($homeVal === '' || $homeVal === null) ? null : $homeVal;
                $data['away'][$field] = ($awayVal === '' || $awayVal === null) ? null : $awayVal;

                if ($data['home'][$field] !== null || $data['away'][$field] !== null) {
                    $hasAnyValue = true;
                }
            }

            if (! $hasAnyValue) {
                $skipped[] = "Ligne {$lineNumber} : match #{$fixtureId} laissé vide, ignoré";

                continue;
            }

            FixtureStatSaver::save($data);
            $updated++;
        }

        return back()->with([
            'imported' => $updated,
            'skipped' => $skipped,
        ]);
    }
}
