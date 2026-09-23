<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Analyse sportive')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <nav class="bg-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-bold text-lg">Analyse sportive</a>
            <a href="/admin" class="text-sm text-slate-300 hover:text-white">Saisie des données</a>
            <a href="{{ route('analyse') }}" class="text-sm text-slate-300 hover:text-white">Analyse d'équipe</a>
            <a href="{{ route('pronostics') }}" class="text-sm text-slate-300 hover:text-white">Pronostics</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
