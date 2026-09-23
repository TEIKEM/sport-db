@php
    $rows = [
        'Buts marqués (moy.)' => $snap['avg_for'],
        'Buts encaissés (moy.)' => $snap['avg_against'],
        'Buts marqués en 2e mi-temps (moy.)' => $snap['avg_h2_for'],
        'Buts encaissés en 2e mi-temps (moy.)' => $snap['avg_h2_against'],
        'Over 0.5 (%)' => $snap['over_0_5'],
        'Over 1.5 (%)' => $snap['over_1_5'],
        'Over 2.5 (%)' => $snap['over_2_5'],
        'Over 3.5 (%)' => $snap['over_3_5'],
        'BTTS (%)' => $snap['btts'],
        'Clean sheets (%)' => $snap['clean_sheets'],
        'Matchs sans marquer (%)' => $snap['failed_to_score'],
    ];
@endphp

<div class="bg-white rounded-lg shadow p-4">
    <h3 class="font-semibold mb-2">
        {{ $title }}
        <span class="text-slate-400 text-sm font-normal">({{ $snap['n'] }} matchs)</span>
    </h3>

    @include('partials.form', ['form' => $snap['form']])

    @if($snap['n'] > 0)
        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
            <dt class="text-slate-500">Victoires / Nuls / Défaites</dt>
            <dd class="text-right font-medium">{{ $snap['wins'] }} / {{ $snap['draws'] }} / {{ $snap['losses'] }}</dd>

            @foreach($rows as $label => $value)
                <dt class="text-slate-500">{{ $label }}</dt>
                <dd class="text-right font-medium">{{ $value ?? '–' }}</dd>
            @endforeach
        </dl>
    @endif
</div>
