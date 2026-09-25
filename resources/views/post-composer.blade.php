@extends('layouts.app')

@section('title', 'Composeur de post')

@section('content')
    <h1 class="text-2xl font-bold mb-1">Composeur de post</h1>
    <p class="text-sm text-slate-500 mb-4">
        Choisis une équipe (et une 2e pour comparer), puis les stats que tu veux inclure dans la liste ci-dessous.
        🟢 vert quand le pourcentage est de 50 % ou plus, 🔴 rouge en dessous.
    </p>

    {{-- Barre de recherche + sélection des stats --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div class="relative">
                <label class="text-sm text-slate-500">Équipe</label>
                <input type="text" id="team-search" autocomplete="off"
                       value="{{ $team->name ?? '' }}" placeholder="Tape le nom d'une équipe..."
                       class="w-full border rounded px-2 py-1 mt-1">
                <input type="hidden" name="team" id="team-id" value="{{ $team->id ?? '' }}">
                <div id="team-suggestions" class="absolute z-10 bg-white border rounded shadow w-full mt-1 hidden"></div>
            </div>

            <div class="relative">
                <label class="text-sm text-slate-500">2e équipe (facultatif)</label>
                <input type="text" id="team2-search" autocomplete="off"
                       value="{{ $team2->name ?? '' }}" placeholder="Tape le nom d'une 2e équipe..."
                       class="w-full border rounded px-2 py-1 mt-1">
                <input type="hidden" name="team2" id="team2-id" value="{{ $team2->id ?? '' }}">
                <div id="team2-suggestions" class="absolute z-10 bg-white border rounded shadow w-full mt-1 hidden"></div>
            </div>
        </div>

        @if($team)
            <div>
                <label class="text-sm text-slate-500">Stats à inclure (Ctrl/Cmd + clic pour en choisir plusieurs)</label>
                <select name="stats[]" multiple size="10" class="w-full border rounded px-2 py-1 mt-1 bg-white text-sm">
                    @foreach($availableStats as $group)
                        <optgroup label="{{ $group['title'] }}">
                            @foreach($group['options'] as $opt)
                                <option value="{{ $opt['key'] }}" @selected(in_array($opt['key'], $selectedKeys))>{{ $opt['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex gap-2">
            <button class="bg-slate-900 text-white rounded px-4 py-1.5 text-sm">Générer le message</button>
            @if($team)
                <a href="{{ route('post-composer') }}" class="text-sm text-slate-500 px-2 py-1.5">Réinitialiser</a>
            @endif
        </div>
    </form>

    @if(!$team)
        <div class="bg-white rounded-lg shadow p-6 text-slate-500 text-sm">
            Tape le nom d'une équipe pour voir la liste des stats disponibles.
        </div>
    @elseif(empty($selectedKeys))
        <div class="bg-white rounded-lg shadow p-6 text-slate-500 text-sm">
            Choisis au moins une stat dans la liste ci-dessus, puis clique sur « Générer le message ».
        </div>
    @else
        {{-- Aperçu coloré --}}
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            @foreach(array_filter([$filtered1, $filtered2]) as $i => $detail)
                @php $name = $i === 0 ? $team->name : $team2->name; @endphp
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <h3 class="font-semibold p-3 border-b bg-slate-50">⚽ {{ $name }} <span class="text-sm font-normal text-slate-400">({{ $detail['n'] }} match(s))</span></h3>

                    @foreach($detail['categories'] as $category)
                        <div class="px-3 pt-2 text-xs font-semibold text-slate-400 uppercase">{{ $category['title'] }}</div>
                        @foreach($category['rows'] as $row)
                            @php
                                $isPct = str_ends_with($row['value'], ' %');
                                $num = $isPct ? (float) str_replace(' %', '', $row['value']) : null;
                                $good = $isPct && $num >= 50;
                            @endphp
                            <div class="flex justify-between items-center px-3 py-1 text-sm
                                {{ $isPct ? ($good ? 'bg-green-50' : 'bg-red-50') : '' }}">
                                <span class="{{ $isPct ? ($good ? 'text-green-700' : 'text-red-700') : 'text-slate-600' }}">
                                    {{ $isPct ? ($good ? '🟢' : '🔴') : '📌' }} {{ $row['label'] }}
                                </span>
                                <span class="font-bold {{ $isPct ? ($good ? 'text-green-700' : 'text-red-700') : 'text-slate-700' }}">
                                    {{ $row['value'] }}
                                </span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Message à copier --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">Message à publier</h3>
                <button onclick="copyMessage()" id="copy-btn" class="text-sm bg-slate-900 text-white rounded px-3 py-1">Copier</button>
            </div>
            <textarea id="post-message" readonly rows="18" class="w-full border rounded p-3 text-sm font-mono bg-slate-50">{{ $message }}</textarea>
        </div>
    @endif

    <script>
        function copyMessage() {
            const el = document.getElementById('post-message');
            if (!el) return;
            el.select();
            navigator.clipboard.writeText(el.value).then(function () {
                const btn = document.getElementById('copy-btn');
                const original = btn.textContent;
                btn.textContent = 'Copié !';
                setTimeout(() => btn.textContent = original, 1500);
            });
        }

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
