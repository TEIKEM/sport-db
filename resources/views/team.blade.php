@extends('layouts.app')

@section('title', $team->name)

@section('content')
    <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:underline">← Tous les matchs</a>

    <div class="mt-2 mb-6">
        <h1 class="text-2xl font-bold">{{ $team->name }}</h1>
        <p class="text-slate-500 text-sm">
            {{ ($team->type ?? 'CLUB') === 'NATIONAL' ? 'Équipe nationale' : 'Club' }}
            @if($team->country) · {{ $team->country }} @endif
        </p>
    </div>

    {{-- Forme --}}
    <h2 class="text-lg font-semibold mb-2">Forme actuelle</h2>

    <div class="grid md:grid-cols-3 gap-4">
        @include('partials.snapshot', ['snap' => $overall, 'title' => 'Tous les matchs'])
        @include('partials.snapshot', ['snap' => $home, 'title' => 'À domicile'])
        @include('partials.snapshot', ['snap' => $away, 'title' => 'À l\'extérieur'])
    </div>

    {{-- Statistiques par saison --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">Statistiques par saison</h2>

    @if($seasonStats->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500">Aucun match terminé pour cette équipe.</div>
    @else
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-right">
                    <tr>
                        <th class="p-2 text-left">Compétition</th>
                        <th class="p-2 text-left">Saison</th>
                        <th class="p-2">J</th>
                        <th class="p-2">V</th>
                        <th class="p-2">N</th>
                        <th class="p-2">D</th>
                        <th class="p-2">BP</th>
                        <th class="p-2">BC</th>
                        <th class="p-2">Pts</th>
                        <th class="p-2">Over 2.5 %</th>
                        <th class="p-2">BTTS %</th>
                        <th class="p-2">Encaissés 2e MT</th>
                    </tr>
                </thead>
                <tbody class="text-right">
                    @foreach($seasonStats as $s)
                        <tr class="border-t">
                            <td class="p-2 text-left">{{ $s->league_name }}</td>
                            <td class="p-2 text-left">{{ $s->season_label }}</td>
                            <td class="p-2">{{ $s->played }}</td>
                            <td class="p-2">{{ $s->wins }}</td>
                            <td class="p-2">{{ $s->draws }}</td>
                            <td class="p-2">{{ $s->losses }}</td>
                            <td class="p-2">{{ $s->goals_for }}</td>
                            <td class="p-2">{{ $s->goals_against }}</td>
                            <td class="p-2 font-bold">{{ $s->points }}</td>
                            <td class="p-2">{{ round(100 * $s->over_2_5 / $s->played) }}</td>
                            <td class="p-2">{{ round(100 * $s->btts / $s->played) }}</td>
                            <td class="p-2">{{ $s->avg_h2_against ?? '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Derniers matchs --}}
    <h2 class="text-lg font-semibold mt-8 mb-2">15 derniers matchs</h2>

    @php
        $colors = ['W' => 'bg-green-500', 'D' => 'bg-yellow-500', 'L' => 'bg-red-500'];
        $labels = ['W' => 'V', 'D' => 'N', 'L' => 'D'];
    @endphp

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="p-2">Date</th>
                    <th class="p-2">Compétition</th>
                    <th class="p-2">Lieu</th>
                    <th class="p-2">Adversaire</th>
                    <th class="p-2 text-center">Score</th>
                    <th class="p-2 text-center">Résultat</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $m)
                    <tr class="border-t hover:bg-slate-50">
                        <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($m->kickoff_at)->format('d/m/Y') }}</td>
                        <td class="p-2 text-slate-500">{{ $m->league_name }}</td>
                        <td class="p-2">{{ $m->is_neutral ? 'Neutre' : ($m->is_home ? 'Dom.' : 'Ext.') }}</td>
                        <td class="p-2">
                            <a href="{{ route('team', $m->opponent_id) }}" class="hover:underline">{{ $m->opponent }}</a>
                        </td>
                        <td class="p-2 text-center font-bold whitespace-nowrap">{{ $m->goals_for }} - {{ $m->goals_against }}</td>
                        <td class="p-2 text-center">
                            <span class="inline-flex w-6 h-6 rounded text-white text-xs font-bold items-center justify-center {{ $colors[$m->result] ?? 'bg-slate-400' }}">{{ $labels[$m->result] ?? '?' }}</span>
                        </td>
                        <td class="p-2 text-right">
                            <a href="{{ route('match', $m->fixture_id) }}" class="text-blue-600 hover:underline">Analyser</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-400">Aucun match terminé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
