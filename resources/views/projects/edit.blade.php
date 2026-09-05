@extends('layouts.app', ['title' => 'Edit '.$project->name])

@section('content')
<a href="{{ route('projects.dashboard', $project) }}" class="text-sm font-semibold text-orange-600 hover:text-orange-700">&larr; Kembali ke dashboard</a>
<div class="mx-auto mt-6 max-w-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Pengaturan proyek</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Edit proyek</h1>
    </div>
    <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <label class="block text-sm font-medium">Nama proyek<input name="name" required class="mt-1 w-full border-slate-300 px-3 py-2" value="{{ old('name', $project->name) }}"></label>
        <label class="block text-sm font-medium">Lokasi<input name="location" class="mt-1 w-full border-slate-300 px-3 py-2" value="{{ old('location', $project->location) }}"></label>
        <div class="grid grid-cols-2 gap-3">
            <label class="block text-sm font-medium">Tanggal mulai<input type="date" name="start_date" class="mt-1 w-full border-slate-300 px-3 py-2" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"></label>
            <label class="block text-sm font-medium">Tanggal selesai<input type="date" name="end_date" class="mt-1 w-full border-slate-300 px-3 py-2" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"></label>
        </div>
        <label class="block text-sm font-medium">Anggaran<input type="number" min="0" step="0.01" name="budget" class="mt-1 w-full border-slate-300 px-3 py-2" value="{{ old('budget', $project->budget) }}"></label>
        <label class="block text-sm font-medium">Status<select name="status" class="mt-1 w-full border-slate-300 px-3 py-2">
            @foreach (['planning' => 'Perencanaan', 'active' => 'Berjalan', 'completed' => 'Selesai', 'on_hold' => 'Ditunda'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $project->status) === $value)>{{ $label }}</option>
            @endforeach
        </select></label>
        <div class="flex items-center justify-between gap-3 pt-3">
            <button class="bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">Simpan perubahan</button>
            <button type="submit" form="delete-project" class="text-sm font-semibold text-red-600 hover:text-red-700" onclick="return confirm('Hapus proyek ini?')">Hapus proyek</button>
        </div>
    </form>
    <form id="delete-project" method="POST" action="{{ route('projects.destroy', $project) }}" class="hidden">@csrf @method('DELETE')</form>
</div>
@endsection
