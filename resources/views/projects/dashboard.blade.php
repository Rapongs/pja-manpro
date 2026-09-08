@extends('layouts.app', ['title' => $project->name])

@section('content')
<a href="{{ route('projects.index') }}" class="text-sm font-semibold text-orange-600 hover:text-orange-700">&larr; Semua proyek</a>
<div class="mt-5 flex flex-col justify-between gap-5 border-b border-slate-200 pb-8 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Dashboard proyek</p>
        <h1 class="mt-2 text-4xl font-bold tracking-tight">{{ $project->name }}</h1>
        <p class="mt-2 text-slate-500">{{ $project->location ?: 'Lokasi belum diisi' }}</p>
    </div>
    <div class="flex items-center gap-3">
        @if (! session('guest_mode', false))
            <a href="{{ route('projects.edit', $project) }}" class="border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:border-orange-500 hover:text-orange-600">Edit proyek</a>
        @endif
        <span class="w-fit bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-700">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
    </div>
</div>
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([['label' => 'Kurva S & Lapjusik', 'route' => 'projects.progress', 'value' => 'Buka progress'], ['label' => 'Laporan Foto', 'route' => 'projects.photos', 'value' => 'Buka gallery'], ['label' => 'Material', 'route' => 'projects.materials', 'value' => 'Buka material'], ['label' => 'Keuangan', 'route' => 'projects.cash-flows', 'value' => 'Buka cash flow']] as $module)
        @if ($module['route'] === 'projects.cash-flows' && session('guest_mode', false)) @continue @endif
        <a href="{{ route($module['route'], $project) }}" class="border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-400"><p class="text-sm text-slate-500">{{ $module['label'] }}</p><p class="mt-4 text-lg font-semibold text-orange-600">{{ $module['value'] }} &rarr;</p></a>
    @endforeach
</div>
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Progress aktual</p><p class="mt-3 text-2xl font-bold">{{ number_format($actualProgress, 2) }}%</p></div>
    @if (! session('guest_mode', false))
    <div class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Saldo kas</p><p class="mt-3 text-2xl font-bold">Rp {{ number_format($cashBalance, 0, ',', '.') }}</p></div>
    @endif
    <div class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Jenis material</p><p class="mt-3 text-2xl font-bold">{{ $materialCount }}</p></div>
    <div class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Laporan foto</p><p class="mt-3 text-2xl font-bold">{{ $photoCount }}</p></div>
</div>
@endsection
