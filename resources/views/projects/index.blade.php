@extends('layouts.app', ['title' => 'Daftar Proyek'])

@section('content')
<div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Workspace proyek</p>
        <h1 class="text-3xl font-bold tracking-tight">Pilih proyek untuk mulai bekerja</h1>
        <p class="mt-2 text-slate-500">Semua laporan, progres, material, dan keuangan terisolasi di dalam proyek.</p>
    </div>
</div>

@if (! session('guest_mode', false))
<div class="mb-8 grid items-start gap-8 lg:grid-cols-[minmax(260px,1fr)_minmax(0,2fr)]">
<section class="border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-lg font-semibold">Tambah proyek</h2>
    <form method="POST" action="{{ route('projects.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        <label class="text-sm font-medium">Nama proyek<input name="name" required placeholder="Nama proyek" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" value="{{ old('name') }}"></label>
        <label class="text-sm font-medium">Lokasi<input name="location" placeholder="Lokasi" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" value="{{ old('location') }}"></label>
        <label class="text-sm font-medium">Tanggal mulai<input type="date" name="start_date" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" value="{{ old('start_date') }}"></label>
        <label class="text-sm font-medium">Tanggal selesai<input type="date" name="end_date" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" value="{{ old('end_date') }}"></label>
        <label class="text-sm font-medium">Anggaran<input type="number" min="0" step="0.01" name="budget" placeholder="Anggaran" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" value="{{ old('budget') }}"></label>
        <label class="text-sm font-medium">Status<select name="status" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">
            @foreach (['planning' => 'Perencanaan', 'active' => 'Berjalan', 'completed' => 'Selesai', 'on_hold' => 'Ditunda'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', 'planning') === $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <button class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-600 md:col-span-2">Simpan proyek</button>
    </form>
</section>
<section>
@else
<div class="mb-8">
<section>
@endif
        <form method="GET" action="{{ route('projects.index') }}" class="mb-5 flex flex-col gap-3 sm:flex-row">
            <input name="search" data-hide-field-label value="{{ request('search') }}" placeholder="Cari nama atau lokasi proyek" class="min-w-0 flex-1 border-slate-300 px-3 py-2 text-sm">
            <select name="sort" data-hide-field-label onchange="this.form.submit()" class="border-slate-300 px-3 py-2 text-sm">
                <option value="created_at" @selected($sort === 'created_at')>Terbaru</option>
                <option value="name" @selected($sort === 'name')>Nama</option>
                <option value="start_date" @selected($sort === 'start_date')>Tanggal mulai</option>
                <option value="status" @selected($sort === 'status')>Status</option>
            </select>
            <button class="border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:border-slate-900">Cari</button>
        </form>
        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($projects as $project)
                <a href="{{ route('projects.dashboard', $project) }}" class="group border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-400">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-semibold group-hover:text-orange-600">{{ $project->name }}</h2>
                        <span class="bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">{{ $project->location ?: 'Lokasi belum diisi' }}</p>
                    <p class="mt-6 text-xs text-slate-400">Mulai {{ $project->start_date?->format('d M Y') ?: 'belum ditentukan' }}</p>
                </a>
            @empty
                <div class="border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500 md:col-span-2">Belum ada proyek yang cocok.</div>
            @endforelse
        </div>
        <div class="mt-6">{{ $projects->links() }}</div>
</section>
</div>
@endsection
