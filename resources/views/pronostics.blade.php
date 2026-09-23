@extends('layouts.app')

@section('title', 'Pronostics')

@section('content')
    <h1 class="text-2xl font-bold mb-1">Pronostics</h1>
    <p class="text-sm text-slate-500 mb-4">
        Ces pourcentages décrivent ce qui s'est passé dans le passé, pas une garantie sur le prochain match.
        Utile pour comparer et repérer des tendances, pas pour prédire à coup sûr.
    </p>

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
            <label class="text-sm text-slate-500">Comparer avec (facultatif)</label>
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
                <a href="{{ route('pronostics') }}" class="text-sm text-slate-500 px-2 py-1.5">Réinitialiser</a>
            @endif
        </div>
    </form>

    @if(!$team)
        <div class="bg-white rounded-lg shadow p-6 text-slate-500 text-sm">
            Tape le nom d'une équipe pour voir toutes ses statistiques en pourcentages. Ajoute une 2e équipe pour les comparer côte à côte.
        </div>
    @else
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xl font-bold">
                {{ $team->name }}
                @if($team2) <span class="text-slate-400 font-normal">vs</span> {{ $team2->name }} @endif
            </h2>
            <span class="text-sm text-slate-500">
                {{ $detail1['n'] }} match(s) analysé(s){{ $team2 ? ' · ' . $detail2['n'] . ' pour ' . $team2->name : '' }}
            </span>
        </div>

        @foreach($detail1['categories'] as $ci => $category)
            <div class="bg-white rounded-lg shadow overflow-x-auto mb-4">
                <h3 class="font-semibold p-3 border-b bg-slate-50">{{ $category['title'] }}</h3>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach($category['rows'] as $ri => $row)
                            <tr class="border-t first:border-t-0">
                                <td class="p-2">{{ $row['label'] }}</td>
                                <td class="p-2 text-right font-bold {{ $team2 ? 'w-24' : 'w-28' }}">{{ $row['value'] }}</td>
                                @if($team2)
                                    <td class="p-2 text-right font-bold w-24 text-blue-700">
                                        {{ $detail2['categories'][$ci]['rows'][$ri]['value'] ?? '–' }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        {{-- Stats de match saisies à la main --}}
        <div class="bg-white rounded-lg shadow overflow-x-auto mb-4">
            <h3 class="font-semibold p-3 border-b bg-slate-50">
                Stats de match (moyennes des stats saisies à la main)
            </h3>

            @if(!$avg1 && !$avg2)
                <p class="p-3 text-sm text-slate-500">Aucune stat de match saisie pour cette sélection.</p>
            @else
                <table class="w-full text-sm">
                    <tbody>
                        @php
                            $matchStatLabels = [
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
                        <tr class="border-t first:border-t-0 text-slate-400 text-xs">
                            <td class="p-2">Matchs avec stats saisies</td>
                            <td class="p-2 text-right">{{ $avg1->n ?? 0 }}</td>
                            @if($team2)<td class="p-2 text-right text-blue-700">{{ $avg2->n ?? 0 }}</td>@endif
                        </tr>
                        @foreach($matchStatLabels as $col => $label)
                            <tr class="border-t">
                                <td class="p-2">{{ $label }}</td>
                                <td class="p-2 text-right font-bold">{{ $avg1->$col ?? '–' }}</td>
                                @if($team2)<td class="p-2 text-right font-bold text-blue-700">{{ $avg2->$col ?? '–' }}</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
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
