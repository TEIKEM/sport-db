@extends('layouts.app')

@section('title', 'Matchs')

@section('content')
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @foreach(['Matchs' => $totals['fixtures'], 'Matchs terminés' => $totals['finished'], 'Équipes' => $totals['teams'], 'Compétitions' => $totals['leagues']] as $label => $value)
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-bold">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-4 gap-6">
        {{-- Liste des compétitions --}}
        <aside class="md:col-span-1 space-y-4">
            @php
                $typeNames = [
                    'LEAGUE' => 'Championnats',
                    'CUP' => 'Coupes nationales',
                    'CONTINENTAL_CLUB' => 'Clubs (continental / monde)',
                    'INTERNATIONAL' => 'Sélections nationales',
                ];
            @endphp

            @foreach($leagues->groupBy('type') as $type => $items)
                <div class="bg-white rounded-lg shadow p-3">
                    <h3 class="font-semibold mb-2">{{ $typeNames[$type] ?? $type }}</h3>
                    <ul class="text-sm space-y-1">
                        @foreach($items as $l)
                            <li>
                                <a class="text-blue-600 hover:underline" href="{{ route('competition', $l) }}">{{ $l->name }}</a>
                                <span class="text-slate-400">{{ $l->country }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </aside>

        {{-- Liste des matchs --}}
        <section class="md:col-span-3">
            <form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom d'une équipe"
                       class="border rounded px-2 py-1 text-sm">

                <select name="league" class="border rounded px-2 py-1 text-sm">
                    <option value="">Toutes les compétitions</option>
                    @foreach($leagues as $l)
                        <option value="{{ $l->id }}" @selected(request('league') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="border rounded px-2 py-1 text-sm">
                    <option value="">Tous les statuts</option>
                    @foreach(['SCHEDULED' => 'Programmés', 'LIVE' => 'En cours', 'FINISHED' => 'Terminés'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <button class="bg-slate-900 text-white rounded px-3 py-1 text-sm">Filtrer</button>
                <a href="{{ route('home') }}" class="text-sm text-slate-500 px-2 py-1">Réinitialiser</a>
            </form>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-2">Date</th>
                            <th class="p-2">Compétition</th>
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
                                <td class="p-2">
                                    {{ $f->league->name }}
                                    <span class="text-slate-400">{{ $f->season->label }}</span>
                                </td>
                                <td class="p-2 text-right">
                                    <a href="{{ route('team', $f->homeTeam) }}" class="hover:underline">{{ $f->homeTeam->name }}</a>
                                </td>
                                <td class="p-2 text-center font-bold whitespace-nowrap">
                                    @if($f->status === 'FINISHED' && $f->home_score !== null)
                                        {{ $f->home_score }} - {{ $f->away_score }}
                                    @else
                                        <span class="text-slate-400 font-normal">vs</span>
                                    @endif
                                </td>
                                <td class="p-2">
                                    <a href="{{ route('team', $f->awayTeam) }}" class="hover:underline">{{ $f->awayTeam->name }}</a>
                                </td>
                                <td class="p-2 text-right">
                                    <a href="{{ route('match', $f) }}" class="text-blue-600 hover:underline">Analyser</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-slate-400">Aucun match trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $fixtures->links() }}</div>
        </section>
    </div>
@endsection
