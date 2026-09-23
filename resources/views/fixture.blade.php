@extends('layouts.app')

@section('title', $fixture->homeTeam->name . ' - ' . $fixture->awayTeam->name)

@section('content')
    <a href="{{ route('competition', $fixture->league) }}" class="text-sm text-blue-600 hover:underline">
        ← {{ $fixture->league->name }} {{ $fixture->season->label }}
    </a>

    {{-- En-tête du match --}}
    <div class="bg-white rounded-lg shadow p-6 mt-3 text-center">
        <p class="text-sm text-slate-500">
            {{ \Carbon\Carbon::parse($fixture->kickoff_at)->format('d/m/Y H:i') }}
            @if($fixture->stage) · {{ str_replace('_', ' ', $fixture->stage) }} @endif
            @if($fixture->group_name) · Groupe {{ $fixture->group_name }} @endif
            @if($fixture->venue) · {{ $fixture->venue }} @endif
            @if($fixture->is_neutral) · Terrain neutre @endif
            @if($fixture->referee) · Arbitre : {{ $fixture->referee->name }} @endif
        </p>

        <div class="flex items-center justify-center gap-6 mt-3">
            <a href="{{ route('team', $fixture->homeTeam) }}" class="text-xl font-bold w-1/3 text-right hover:underline">{{ $fixture->homeTeam->name }}</a>

            <div class="text-3xl font-extrabold whitespace-nowrap">
                @if($fixture->status === 'FINISHED' && $fixture->home_score !== null)
                    {{ $fixture->home_score }} - {{ $fixture->away_score }}
                @else
                    <span class="text-slate-400">vs</span>
                @endif
            </div>

            <a href="{{ route('team', $fixture->awayTeam) }}" class="text-xl font-bold w-1/3 text-left hover:underline">{{ $fixture->awayTeam->name }}</a>
        </div>

        <p class="text-sm text-slate-500 mt-2">
            @if($fixture->ht_home_score !== null && $fixture->ht_away_score !== null)
                Mi-temps : {{ $fixture->ht_home_score }} - {{ $fixture->ht_away_score }}
            @endif
            @if($fixture->et_home_score !== null && $fixture->et_away_score !== null)
                · Après prolongations : {{ $fixture->et_home_score }} - {{ $fixture->et_away_score }}
            @endif
            @if($fixture->pen_home_score !== null && $fixture->pen_away_score !== null)
                · Tirs au but : {{ $fixture->pen_home_score }} - {{ $fixture->pen_away_score }}
            @endif
        </p>
        <p class="text-xs text-slate-400 mt-1">Le score affiché est celui après 90 minutes. Statut : {{ $fixture->status }}</p>
    </div>

    {{-- Analyse avant-match --}}
    <h2 class="text-lg font-semibold mt-8 mb-1">Analyse avant-match</h2>
    <p class="text-sm text-slate-500 mb-3">Calculée uniquement à partir des matchs joués avant celui-ci (10 derniers matchs, 5 derniers à domicile / à l'extérieur).</p>

    <div class="grid md:grid-cols-2 gap-4">
        @include('partials.snapshot', ['snap' => $home, 'title' => $fixture->homeTeam->name . ' - forme générale'])
        @include('partials.snapshot', ['snap' => $away, 'title' => $fixture->awayTeam->name . ' - forme générale'])
        @include('partials.snapshot', ['snap' => $homeVenue, 'title' => $fixture->homeTeam->name . ' - à domicile'])
        @include('partials.snapshot', ['snap' => $awayVenue, 'title' => $fixture->awayTeam->name . ' - à l\'extérieur'])
    </div>

    @if($expected !== null)
        <div class="bg-white rounded-lg shadow p-4 mt-4 text-sm">
            <span class="font-semibold">Buts attendus (estimation simple) : {{ $expected }}</span>
            <span class="text-slate-500">
                - moyenne croisée entre les buts marqués d'une équipe et les buts encaissés de l'autre. C'est un repère très approximatif, pas une prévision fiable.
            </span>
        </div>
    @endif

    {{-- Face-à-face --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">Face-à-face</h2>

    @if($h2hSummary['n'] === 0)
        <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500">Aucun match précédent saisi entre ces deux équipes.</div>
    @else
        <div class="bg-white rounded-lg shadow p-4 mb-3 text-sm">
            <p>
                Sur {{ $h2hSummary['n'] }} matchs :
                <span class="font-medium">{{ $fixture->homeTeam->name }} {{ $h2hSummary['home_wins'] }} V</span>,
                <span class="font-medium">{{ $h2hSummary['draws'] }} nuls</span>,
                <span class="font-medium">{{ $fixture->awayTeam->name }} {{ $h2hSummary['away_wins'] }} V</span>
            </p>
            <p class="text-slate-500 mt-1">
                Moyenne de {{ $h2hSummary['avg_goals'] }} buts par match · Over 2.5 : {{ $h2hSummary['over_2_5'] }} % · BTTS : {{ $h2hSummary['btts'] }} %
            </p>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                    @foreach($h2h as $m)
                        <tr class="border-t first:border-t-0">
                            <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($m->kickoff_at)->format('d/m/Y') }}</td>
                            <td class="p-2 text-slate-500">{{ $m->league->name }}</td>
                            <td class="p-2 text-right">{{ $m->homeTeam->name }}</td>
                            <td class="p-2 text-center font-bold whitespace-nowrap">{{ $m->home_score }} - {{ $m->away_score }}</td>
                            <td class="p-2">{{ $m->awayTeam->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Statistiques du match --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">Statistiques du match</h2>

    @php
        $hs = $stats->get($fixture->home_team_id);
        $as = $stats->get($fixture->away_team_id);
        $fields = [
            'possession' => 'Possession (%)',
            'shots' => 'Tirs',
            'shots_on_target' => 'Tirs cadrés',
            'corners' => 'Corners',
            'fouls' => 'Fautes',
            'yellow_cards' => 'Cartons jaunes',
            'red_cards' => 'Cartons rouges',
            'xg' => 'xG',
        ];
    @endphp

    @if(!$hs && !$as)
        <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500">Aucune statistique saisie pour ce match.</div>
    @else
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                    @foreach($fields as $col => $label)
                        <tr class="border-t first:border-t-0">
                            <td class="p-2 text-right w-1/3 font-medium">{{ $hs->$col ?? '–' }}</td>
                            <td class="p-2 text-center text-slate-500">{{ $label }}</td>
                            <td class="p-2 w-1/3 font-medium">{{ $as->$col ?? '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Événements --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">Événements</h2>

    @php
        $typeLabels = [
            'GOAL' => 'But',
            'OWN_GOAL' => 'But contre son camp',
            'PENALTY_GOAL' => 'But sur penalty',
            'PENALTY_MISSED' => 'Penalty manqué',
            'YELLOW' => 'Carton jaune',
            'RED' => 'Carton rouge',
            'SUB' => 'Remplacement',
        ];
    @endphp

    @if($events->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500">Aucun événement saisi pour ce match.</div>
    @else
        <div class="bg-white rounded-lg shadow p-2">
            @foreach($events as $e)
                <div class="flex gap-3 text-sm py-2 border-t first:border-t-0 px-2">
                    <span class="w-10 font-bold text-slate-500">{{ $e->minute }}'</span>
                    <span class="w-44">{{ $typeLabels[$e->type] ?? $e->type }}</span>
                    <span>
                        {{ $e->player?->name }}
                        <span class="text-slate-400">{{ $e->team?->name }}</span>
                        {{ $e->notes }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Cotes --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">Cotes</h2>

    @if($oddsGroups->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500">Aucune cote saisie pour ce match.</div>
    @else
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="p-2">Bookmaker</th>
                        <th class="p-2">Marché</th>
                        <th class="p-2">Sélection</th>
                        <th class="p-2 text-right">Ouverture</th>
                        <th class="p-2 text-right">Dernière</th>
                        <th class="p-2 text-right">Variation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($oddsGroups as $rows)
                        @php
                            $first = $rows->first();
                            $last = $rows->last();
                            $diff = round($last->price - $first->price, 2);
                        @endphp
                        <tr class="border-t">
                            <td class="p-2">{{ $first->bookmaker }}</td>
                            <td class="p-2">{{ $first->market }}</td>
                            <td class="p-2">{{ $first->selection }}</td>
                            <td class="p-2 text-right">{{ number_format($first->price, 2) }}</td>
                            <td class="p-2 text-right font-medium">{{ number_format($last->price, 2) }}</td>
                            <td class="p-2 text-right {{ $diff > 0 ? 'text-red-600' : ($diff < 0 ? 'text-green-600' : 'text-slate-400') }}">
                                {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Paris / simulations --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">Paris sur ce match</h2>

    @if($fixture->trades->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500">
            Aucun pari enregistré. Ajoute-en dans le panneau de saisie (menu Trades).
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="p-2">Type</th>
                        <th class="p-2">Marché</th>
                        <th class="p-2">Sélection</th>
                        <th class="p-2 text-right">Mise</th>
                        <th class="p-2 text-right">Cote entrée</th>
                        <th class="p-2 text-right">Cote sortie</th>
                        <th class="p-2">Résultat</th>
                        <th class="p-2 text-right">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fixture->trades as $t)
                        <tr class="border-t">
                            <td class="p-2">{{ $t->is_simulated ? 'Simulé' : 'Réel' }}</td>
                            <td class="p-2">{{ $t->market }}</td>
                            <td class="p-2">{{ $t->selection }}</td>
                            <td class="p-2 text-right">{{ number_format($t->stake, 2) }}</td>
                            <td class="p-2 text-right">{{ number_format($t->odds_in, 2) }}</td>
                            <td class="p-2 text-right">{{ $t->odds_out !== null ? number_format($t->odds_out, 2) : '–' }}</td>
                            <td class="p-2">{{ $t->result }}</td>
                            <td class="p-2 text-right">{{ $t->profit !== null ? number_format($t->profit, 2) : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Notes --}}
    @if($fixture->notes)
        <h2 class="text-lg font-semibold mt-8 mb-2">Notes</h2>
        <div class="bg-white rounded-lg shadow p-4 text-sm whitespace-pre-line">{{ $fixture->notes }}</div>
    @endif
@endsection
