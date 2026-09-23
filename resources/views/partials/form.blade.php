@php
    $colors = ['W' => 'bg-green-500', 'D' => 'bg-yellow-500', 'L' => 'bg-red-500'];
    $labels = ['W' => 'V', 'D' => 'N', 'L' => 'D'];
@endphp

<div class="flex gap-1 items-center">
    @forelse($form as $r)
        <span class="w-6 h-6 rounded text-white text-xs font-bold flex items-center justify-center {{ $colors[$r] ?? 'bg-slate-400' }}">{{ $labels[$r] ?? '?' }}</span>
    @empty
        <span class="text-sm text-slate-400">Aucun match terminé</span>
    @endforelse
</div>
