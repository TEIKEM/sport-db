<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Support\TeamStatsService;
use Illuminate\Http\Request;

class PostComposerController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->filled('team') ? Team::find($request->integer('team')) : null;
        $team2 = $request->filled('team2') ? Team::find($request->integer('team2')) : null;
        $selectedKeys = $request->input('stats', []);

        $detail1 = null;
        $filtered1 = null;
        $filtered2 = null;
        $availableStats = collect();
        $message = null;

        if ($team) {
            $detail1 = TeamStatsService::detailed(TeamStatsService::matches($team->id));

            // La liste des stats disponibles est toujours la même structure, peu importe l'équipe
            $availableStats = collect($detail1['categories'])->map(fn ($c) => [
                'title' => $c['title'],
                'options' => collect($c['rows'])->map(fn ($r) => ['key' => $r['key'], 'label' => $r['label']]),
            ]);

            $filtered1 = TeamStatsService::filterByKeys($detail1, $selectedKeys);

            if ($team2) {
                $detail2 = TeamStatsService::detailed(TeamStatsService::matches($team2->id));
                $filtered2 = TeamStatsService::filterByKeys($detail2, $selectedKeys);
            }

            if ($selectedKeys !== []) {
                $blocks = [$this->buildBlock($team->name, $filtered1)];

                if ($team2) {
                    $blocks[] = $this->buildBlock($team2->name, $filtered2);
                }

                $message = $this->buildMessage($team, $team2, $blocks);
            }
        }

        return view('post-composer', compact(
            'team', 'team2', 'selectedKeys', 'availableStats',
            'filtered1', 'filtered2', 'message'
        ));
    }

    /**
     * Construit le bloc texte d'une équipe : une ligne par stat sélectionnée, avec
     * 🟢/🔴 pour les pourcentages (≥ 50 % vert, < 50 % rouge) et 📌 pour les moyennes.
     */
    private function buildBlock(string $teamName, array $detail): string
    {
        $lines = ["⚽ {$teamName} (" . $detail['n'] . ' match(s) analysé(s))', ''];

        foreach ($detail['categories'] as $category) {
            $rows = [];

            foreach ($category['rows'] as $row) {
                $value = $row['value'];

                if ($value === '–') {
                    continue;
                }

                if (str_ends_with($value, ' %')) {
                    $num = (float) str_replace(' %', '', $value);
                    $emoji = $num >= 50 ? '🟢' : '🔴';
                    $rows[] = "{$emoji} {$row['label']} : {$value}";
                } else {
                    $rows[] = "📌 {$row['label']} : {$value}";
                }
            }

            if ($rows !== []) {
                $lines[] = "— {$category['title']} —";
                array_push($lines, ...$rows);
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    private function buildMessage(Team $team, ?Team $team2, array $blocks): string
    {
        $title = $team2 ? "🔥 {$team->name} vs {$team2->name} — Les stats à connaître 🔥" : "🔥 Stats à connaître : {$team->name} 🔥";

        $parts = [$title, '', implode("\n", $blocks)];
        $parts[] = "⚠️ Une statistique passée n'est jamais une garantie pour le prochain match. À prendre comme un repère, pas une certitude.";

        return implode("\n", $parts);
    }
}
