@extends('layouts.app')

@section('title', $league->name)

@section('content')
    <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:underline">← Tous les matchs</a>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-2 mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $league->name }}</h1>
            <p class="text-slate-500 text-sm">{{ $league->country }}</p>
        </div>

        @if($seasons->count() > 0)
            <form method="GET">
                <select name="season" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm bg-white">
                    @foreach($seasons as $s)
                        <option value="{{ $s->id }}" @selected($season && $season->id === $s->id)>Saison {{ $s->label }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(!$season)
        <div class="bg-white rounded-lg shadow p-6 text-slate-500">
            Aucune saison n'existe pour cette compétition. Crée-en une dans le panneau de saisie.
        </div>
    @else
        {{-- Classements --}}
        <h2 class="text-lg font-semibold mb-2">Classement</h2>

        @forelse($standings as $group => $rows)
            <div class="bg-white rounded-lg shadow mb-4 overflow-x-auto">
                @if($group !== '')
                    <h3 class="font-semibold p-3 border-b">Groupe {{ $group }}</h3>
                @endif

                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-right">
                        <tr>
                            <th class="p-2 text-left w-10">#</th>
                            <th class="p-2 text-left">Équipe</th>
                            <th class="p-2">J</th>
                            <th class="p-2">V</th>
                            <th class="p-2">N</th>
                            <th class="p-2">D</th>
                            <th class="p-2">BP</th>
                            <th class="p-2">BC</th>
                            <th class="p-2">Diff</th>
                            <th class="p-2">Pts</th>
                        </tr>
                    </thead>
                    <tbody class="text-right">
                        @foreach($rows as $r)
                            <tr class="border-t hover:bg-slate-50">
                                <td class="p-2 text-left font-medium">{{ $r->pos }}</td>
                                <td class="p-2 text-left">
                                    <a href="{{ route('team', $r->team_id) }}" class="hover:underline">{{ $r->team_name }}</a>
                                </td>
                                <td class="p-2">{{ $r->played }}</td>
                                <td class="p-2">{{ $r->wins }}</td>
                                <td class="p-2">{{ $r->draws }}</td>
                                <td class="p-2">{{ $r->losses }}</td>
                                <td class="p-2">{{ $r->goals_for }}</td>
                                <td class="p-2">{{ $r->goals_against }}</td>
                                <td class="p-2">{{ $r->goal_diff }}</td>
                                <td class="p-2 font-bold">{{ $r->points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-6 text-slate-500 mb-4">
                Pas encore de classement : aucun match terminé (statut « Terminé » avec score) pour cette saison.
            </div>
        @endforelse

        {{-- Tendances --}}
        @if(count($tops) > 0)
            <h2 class="text-lg font-semibold mt-6 mb-2">Tendances <span class="text-sm font-normal text-slate-400">(équipes avec au moins 3 matchs)</span></h2>

            <div class="grid md:grid-cols-2 gap-4 mb-6">
                @foreach($tops as $title => $items)
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-semibold mb-2 text-sm">{{ $title }}</h3>

                        @forelse($items as $i)
                            <div class="flex justify-between text-sm py-0.5">
                                <a href="{{ route('team', $i->id) }}" class="text-blue-600 hover:underline">{{ $i->name }}</a>
                                <span class="font-medium">{{ $i->value }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">Pas assez de données.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Derniers matchs --}}
        <h2 class="text-lg font-semibold mb-2">Matchs de la saison</h2>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="p-2">Date</th>
                        <th class="p-2">Phase</th>
                        <th class="p-2 text-right">Domicile</th>
                        <th class="p-2 text-center">Score</th>
                        <th class="p-2">Extérieur</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fixtures as $f)
                        <tr class="border-t hover:bg-slate-50">
                            <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($f->kickoff_at)->format('d/m/Y H:i') }}</td>
                            <td class="p-2 text-slate-500">
                                {{ $f->stage ? str_replace('_', ' ', $f->stage) : '' }}
                                {{ $f->group_name ? '(Groupe ' . $f->group_name . ')' : '' }}
                            </td>
                            <td class="p-2 text-right">{{ $f->homeTeam->name }}</td>
                            <td class="p-2 text-center font-bold whitespace-nowrap">
                                @if($f->status === 'FINISHED' && $f->home_score !== null)
                                    {{ $f->home_score }} - {{ $f->away_score }}
                                @else
                                    <span class="text-slate-400 font-normal">vs</span>
                                @endif
                            </td>
                            <td class="p-2">{{ $f->awayTeam->name }}</td>
                            <td class="p-2 text-right">
                                <a href="{{ route('match', $f) }}" class="text-blue-600 hover:underline">Analyser</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400">Aucun match saisi pour cette saison.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
