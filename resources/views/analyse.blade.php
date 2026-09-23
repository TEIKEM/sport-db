@extends('layouts.app')

@section('title', 'Recherche équipe')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Analyse d'équipe</h1>

    {{-- Barre de recherche --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid md:grid-cols-2 gap-4">
        <div class="relative">
            <label class="text-sm text-slate-500">Équipe</label>
            <input type="text" id="team-search" autocomplete="off"
                   value="{{ $team->name ?? '' }}" placeholder="Tape le nom d'une équipe..."
                   class="w-full border rounded px-2 py-1 mt-1">
            <input type="hidden" name="team" id="team-id" value="{{ $team->id ?? '' }}">
            <div id="team-suggestions" class="absolute z-10 bg-white border rounded shadow w-full mt-1 hidden"></div>
        </div>

        <div class="relative">
            <label class="text-sm text-slate-500">Comparer avec (facultatif, pour la confrontation directe)</label>
            <input type="text" id="team2-search" autocomplete="off"
                   value="{{ $team2->name ?? '' }}" placeholder="Tape le nom d'une 2e équipe..."
                   class="w-full border rounded px-2 py-1 mt-1">
            <input type="hidden" name="team2" id="team2-id" value="{{ $team2->id ?? '' }}">
            <div id="team2-suggestions" class="absolute z-10 bg-white border rounded shadow w-full mt-1 hidden"></div>
        </div>

        @if($team)
            <div>
                <label class="text-sm text-slate-500">Compétition</label>
                <select name="league_id" class="w-full border rounded px-2 py-1 mt-1 bg-white">
                    <option value="">Toutes les compétitions</option>
                    @foreach($leagues as $l)
                        <option value="{{ $l->id }}" @selected($filters['league_id'] == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm text-slate-500">Saison</label>
                <select name="season_id" class="w-full border rounded px-2 py-1 mt-1 bg-white">
                    <option value="">Toutes les saisons</option>
                    @foreach($seasons as $s)
                        <option value="{{ $s->id }}" @selected($filters['season_id'] == $s->id)>{{ $s->league_name }} {{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="md:col-span-2 flex gap-2">
            <button class="bg-slate-900 text-white rounded px-4 py-1.5 text-sm">Afficher</button>
            @if($team)
                <a href="{{ route('analyse') }}" class="text-sm text-slate-500 px-2 py-1.5">Réinitialiser</a>
            @endif
        </div>
    </form>

    @if(!$team)
        <div class="bg-white rounded-lg shadow p-6 text-slate-500 text-sm">
            Tape le nom d'une équipe ci-dessus pour voir son historique complet : forme globale, stats par saison, par compétition, et une confrontation directe si tu ajoutes une 2e équipe.
        </div>
    @else
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">
                <a href="{{ route('team', $team) }}" class="hover:underline">{{ $team->name }}</a>
                @if($filters['league_id'] || $filters['season_id'])
                    <span class="text-sm font-normal text-slate-500">(filtré)</span>
                @endif
            </h2>
        </div>

        {{-- Vue globale (ou filtrée si compétition/saison choisie) --}}
        <h3 class="font-semibold mb-2">Vue d'ensemble</h3>
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            @include('partials.snapshot', ['snap' => $global, 'title' => $filters['league_id'] || $filters['season_id'] ? 'Sélection filtrée' : 'Tous les matchs enregistrés'])
        </div>

        {{-- Confrontation directe --}}
        @if($team2)
            <h3 class="font-semibold mb-2">
                Confrontation directe : {{ $team->name }} contre {{ $team2->name }}
                @if($filters['league_id'] || $filters['season_id'])
                    <span class="text-sm font-normal text-slate-500">(filtrée)</span>
                @endif
            </h3>

            @if($h2h['n'] === 0)
                <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500 mb-6">Aucun match enregistré entre ces deux équipes pour cette sélection.</div>
            @else
                <div class="grid md:grid-cols-2 gap-4 mb-3">
                    @include('partials.snapshot', ['snap' => $h2h, 'title' => $team->name . ' face à ' . $team2->name])
                </div>

                <div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="p-2">Date</th>
                                <th class="p-2">Compétition</th>
                                <th class="p-2">Lieu</th>
                                <th class="p-2 text-center">Score</th>
                                <th class="p-2 text-center">Résultat</th>
                                <th class="p-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($h2hMatches as $m)
                                <tr class="border-t">
                                    <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($m->kickoff_at)->format('d/m/Y') }}</td>
                                    <td class="p-2 text-slate-500">{{ $m->league_name }} {{ $m->season_label }}</td>
                                    <td class="p-2">{{ $m->is_neutral ? 'Neutre' : ($m->is_home ? 'Domicile' : 'Extérieur') }}</td>
                                    <td class="p-2 text-center font-bold whitespace-nowrap">{{ $m->goals_for }} - {{ $m->goals_against }}</td>
                                    <td class="p-2 text-center">{{ $m->result }}</td>
                                    <td class="p-2 text-right"><a href="{{ route('match', $m->fixture_id) }}" class="text-blue-600 hover:underline">Voir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="bg-white rounded-lg shadow p-3 text-sm text-slate-500 mb-6">
                Ajoute une 2e équipe dans la barre de recherche pour voir leur historique face-à-face.
            </div>
        @endif

        {{-- Stats par saison --}}
        <h3 class="font-semibold mb-2">Stats par saison</h3>
        @if($bySeason->isEmpty())
            <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500 mb-6">Aucun match terminé.</div>
        @else
            <div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-2">Compétition</th>
                            <th class="p-2">Saison</th>
                            <th class="p-2 text-right">J</th>
                            <th class="p-2 text-right">V</th>
                            <th class="p-2 text-right">N</th>
                            <th class="p-2 text-right">D</th>
                            <th class="p-2 text-right">BP</th>
                            <th class="p-2 text-right">BC</th>
                            <th class="p-2 text-right">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bySeason as $s)
                            <tr class="border-t">
                                <td class="p-2">
                                    <a href="{{ route('analyse', ['team' => $team->id, 'league_id' => $s->league_id, 'season_id' => $s->season_id]) }}" class="text-blue-600 hover:underline">{{ $s->league_name }}</a>
                                </td>
                                <td class="p-2">{{ $s->season_label }}</td>
                                <td class="p-2 text-right">{{ $s->played }}</td>
                                <td class="p-2 text-right">{{ $s->wins }}</td>
                                <td class="p-2 text-right">{{ $s->draws }}</td>
                                <td class="p-2 text-right">{{ $s->losses }}</td>
                                <td class="p-2 text-right">{{ $s->goals_for }}</td>
                                <td class="p-2 text-right">{{ $s->goals_against }}</td>
                                <td class="p-2 text-right font-bold">{{ $s->points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Stats par compétition --}}
        <h3 class="font-semibold mb-2">Stats par compétition <span class="text-sm font-normal text-slate-400">(toutes saisons confondues)</span></h3>
        @if($byCompetition->isEmpty())
            <div class="bg-white rounded-lg shadow p-4 text-sm text-slate-500 mb-6">Aucun match terminé.</div>
        @else
            <div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-2">Compétition</th>
                            <th class="p-2 text-right">J</th>
                            <th class="p-2 text-right">V</th>
                            <th class="p-2 text-right">N</th>
                            <th class="p-2 text-right">D</th>
                            <th class="p-2 text-right">BP</th>
                            <th class="p-2 text-right">BC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byCompetition as $c)
                            <tr class="border-t">
                                <td class="p-2">
                                    <a href="{{ route('analyse', ['team' => $team->id, 'league_id' => $c->league_id]) }}" class="text-blue-600 hover:underline">{{ $c->league_name }}</a>
                                </td>
                                <td class="p-2 text-right">{{ $c->played }}</td>
                                <td class="p-2 text-right">{{ $c->wins }}</td>
                                <td class="p-2 text-right">{{ $c->draws }}</td>
                                <td class="p-2 text-right">{{ $c->losses }}</td>
                                <td class="p-2 text-right">{{ $c->goals_for }}</td>
                                <td class="p-2 text-right">{{ $c->goals_against }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Historique des matchs (recap) --}}
        <h3 class="font-semibold mb-2">
            Historique des matchs
            <span class="text-sm font-normal text-slate-400">({{ $global['n'] ?? 0 }} au total{{ $matches->count() < ($global['n'] ?? 0) ? ', 50 plus récents affichés' : '' }})</span>
        </h3>

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
                    @forelse($matches as $m)
                        <tr class="border-t hover:bg-slate-50">
                            <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($m->kickoff_at)->format('d/m/Y') }}</td>
                            <td class="p-2 text-slate-500">{{ $m->league_name }} {{ $m->season_label }}</td>
                            <td class="p-2">{{ $m->is_neutral ? 'Neutre' : ($m->is_home ? 'Domicile' : 'Extérieur') }}</td>
                            <td class="p-2">
                                <a href="{{ route('analyse', ['team' => $m->opponent_id]) }}" class="hover:underline">{{ $m->opponent_name }}</a>
                            </td>
                            <td class="p-2 text-center font-bold whitespace-nowrap">{{ $m->goals_for }} - {{ $m->goals_against }}</td>
                            <td class="p-2 text-center">{{ $m->result }}</td>
                            <td class="p-2 text-right"><a href="{{ route('match', $m->fixture_id) }}" class="text-blue-600 hover:underline">Voir</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-slate-400">Aucun match terminé pour cette sélection.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <script>
        function setupTeamSearch(inputId, hiddenId, boxId) {
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const box = document.getElementById(boxId);
            let timer = null;

            input.addEventListener('input', function () {
                hidden.value = '';
                clearTimeout(timer);
                const q = input.value.trim();

                if (q.length < 2) {
                    box.classList.add('hidden');
                    box.innerHTML = '';
                    return;
                }

                timer = setTimeout(function () {
                    fetch('{{ route("teams.search") }}?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(teams => {
                            if (teams.length === 0) {
                                box.classList.add('hidden');
                                box.innerHTML = '';
                                return;
                            }
                            box.innerHTML = teams.map(t =>
                                `<div class="px-3 py-1.5 hover:bg-slate-100 cursor-pointer text-sm" data-id="${t.id}" data-name="${t.name}">${t.name}</div>`
                            ).join('');
                            box.classList.remove('hidden');

                            box.querySelectorAll('[data-id]').forEach(el => {
                                el.addEventListener('click', function () {
                                    input.value = this.dataset.name;
                                    hidden.value = this.dataset.id;
                                    box.classList.add('hidden');
                                });
                            });
                        });
                }, 200);
            });

            document.addEventListener('click', function (e) {
                if (!box.contains(e.target) && e.target !== input) {
                    box.classList.add('hidden');
                }
            });
        }

        setupTeamSearch('team-search', 'team-id', 'team-suggestions');
        setupTeamSearch('team2-search', 'team2-id', 'team2-suggestions');
    </script>
@endsection
