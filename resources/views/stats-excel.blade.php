@extends('layouts.app')

@section('title', 'Stats via Excel')

@section('content')
    <h1 class="text-2xl font-bold mb-1">Remplir les stats via Excel</h1>
    <p class="text-sm text-slate-500 mb-6">
        Exporte les matchs joués dont les stats ne sont pas encore saisies, remplis-les dans Excel,
        puis recharge le fichier ici. Ne modifie jamais la colonne <strong>fixture_id</strong> : c'est elle qui permet de retrouver le bon match.
    </p>

    @if(session('error'))
        <div class="bg-red-50 text-red-700 rounded-lg p-3 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    @if(session('imported') !== null)
        <div class="bg-green-50 text-green-700 rounded-lg p-3 mb-4 text-sm">
            {{ session('imported') }} match(s) mis à jour.
        </div>
    @endif

    @if(session('skipped') && count(session('skipped')) > 0)
        <div class="bg-amber-50 text-amber-700 rounded-lg p-3 mb-6 text-sm">
            <p class="font-semibold mb-1">{{ count(session('skipped')) }} ligne(s) ignorée(s) :</p>
            <ul class="list-disc list-inside">
                @foreach(session('skipped') as $s)
                    <li>{{ $s }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Export --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h2 class="font-semibold mb-3">1. Exporter les matchs sans stats</h2>

        <p class="text-sm text-slate-600 mb-3">
            <strong>{{ $count }}</strong> match(s) terminé(s) sans aucune stat saisie
            @if($leagueId) pour cette compétition @endif.
            @if($count >= 500)
                <span class="text-amber-600">Seuls les 500 premiers seront exportés à la fois — filtre par compétition pour couvrir le reste.</span>
            @endif
        </p>

        <form method="GET" class="flex flex-wrap gap-2 mb-3">
            <select name="league_id" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm bg-white">
                <option value="">Toutes les compétitions</option>
                @foreach($leagues as $l)
                    <option value="{{ $l->id }}" @selected($leagueId == $l->id)>{{ $l->name }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('stats-excel.export', ['league_id' => $leagueId]) }}"
           class="inline-block bg-slate-900 text-white rounded px-4 py-1.5 text-sm {{ $count === 0 ? 'opacity-50 pointer-events-none' : '' }}">
            Télécharger le fichier Excel
        </a>
    </div>

    {{-- Import --}}
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">2. Recharger le fichier rempli</h2>

        <form method="POST" action="{{ route('stats-excel.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls" required class="text-sm">
            <button class="bg-slate-900 text-white rounded px-4 py-1.5 text-sm">Importer</button>
        </form>

        <p class="text-xs text-slate-400 mt-2">
            Les colonnes vides sont ignorées (le match reste tel quel sur ces champs). Une ligne entièrement vide est ignorée.
        </p>
    </div>
@endsection
