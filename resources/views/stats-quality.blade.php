@extends('layouts.app')

@section('title', 'Qualité des stats')

@section('content')
    <h1 class="text-2xl font-bold mb-1">Suivi et qualité des stats</h1>
    <p class="text-sm text-slate-500 mb-6">
        En haut : où concentrer ta saisie. En bas : les valeurs qui ressemblent à des erreurs de frappe dans ce qui est déjà saisi.
    </p>

    {{-- Couverture --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto mb-8">
        <h2 class="font-semibold p-3 border-b">Couverture des stats par compétition</h2>

        @if($coverage->isEmpty())
            <p class="p-3 text-sm text-slate-500">Aucun match terminé pour l'instant.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="p-2">Compétition</th>
                        <th class="p-2 text-right">Matchs terminés</th>
                        <th class="p-2 text-right">Complets (2 équipes)</th>
                        <th class="p-2 text-right">Partiels (1 équipe)</th>
                        <th class="p-2 text-right">Sans stats</th>
                        <th class="p-2 w-48">Avancement</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($coverage as $c)
                        <tr class="border-t">
                            <td class="p-2 font-medium">{{ $c->league_name }}</td>
                            <td class="p-2 text-right">{{ $c->finished }}</td>
                            <td class="p-2 text-right text-green-700">{{ $c->complete }}</td>
                            <td class="p-2 text-right text-amber-600">{{ $c->partial }}</td>
                            <td class="p-2 text-right text-red-600">{{ $c->empty }}</td>
                            <td class="p-2">
                                <div class="w-full bg-slate-100 rounded h-2.5">
                                    <div class="bg-green-500 h-2.5 rounded" style="width: {{ $c->percent }}%"></div>
                                </div>
                                <span class="text-xs text-slate-400">{{ $c->percent }} %</span>
                            </td>
                            <td class="p-2 text-right">
                                <a href="{{ route('stats-excel', ['league_id' => $c->league_id]) }}" class="text-blue-600 hover:underline text-sm">Exporter Excel</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Anomalies sur une stat --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto mb-8">
        <h2 class="font-semibold p-3 border-b">Valeurs suspectes ({{ $anomalies->count() }})</h2>

        @if($anomalies->isEmpty())
            <p class="p-3 text-sm text-slate-500">Aucune anomalie détectée. 👍</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="p-2">Date</th>
                        <th class="p-2">Compétition</th>
                        <th class="p-2">Équipe</th>
                        <th class="p-2">Problème</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($anomalies as $a)
                        <tr class="border-t bg-amber-50">
                            <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($a->kickoff_at)->format('d/m/Y') }}</td>
                            <td class="p-2">{{ $a->league_name }}</td>
                            <td class="p-2">{{ $a->team_name }}</td>
                            <td class="p-2 text-amber-700">{{ $a->anomaly }}</td>
                            <td class="p-2 text-right">
                                <a href="{{ route('match', $a->fixture_id) }}" class="text-blue-600 hover:underline mr-2">Voir</a>
                                <a href="/admin/fixture-stats/{{ $a->stat_id }}/edit" class="text-blue-600 hover:underline">Corriger</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Possession qui ne totalise pas ~100% --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <h2 class="font-semibold p-3 border-b">Possession des 2 équipes qui ne totalise pas ~100 % ({{ $possessionIssues->count() }})</h2>

        @if($possessionIssues->isEmpty())
            <p class="p-3 text-sm text-slate-500">Aucun souci détecté. 👍</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="p-2">Date</th>
                        <th class="p-2">Compétition</th>
                        <th class="p-2">Match</th>
                        <th class="p-2 text-right">Possession dom.</th>
                        <th class="p-2 text-right">Possession ext.</th>
                        <th class="p-2 text-right">Total</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($possessionIssues as $p)
                        <tr class="border-t bg-amber-50">
                            <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($p->kickoff_at)->format('d/m/Y') }}</td>
                            <td class="p-2">{{ $p->league_name }}</td>
                            <td class="p-2">{{ $p->home_name }} - {{ $p->away_name }}</td>
                            <td class="p-2 text-right">{{ $p->home_possession }} %</td>
                            <td class="p-2 text-right">{{ $p->away_possession }} %</td>
                            <td class="p-2 text-right font-medium">{{ $p->home_possession + $p->away_possession }} %</td>
                            <td class="p-2 text-right">
                                <a href="{{ route('match', $p->fixture_id) }}" class="text-blue-600 hover:underline">Voir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
