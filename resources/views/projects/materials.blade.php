@extends('layouts.app', ['title' => 'Material · '.$project->name])
@section('content')
<a href="{{ route('projects.dashboard', $project) }}" class="text-sm font-semibold text-orange-600">&larr; Dashboard proyek</a>
<div class="mt-5"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Material</p><h1 class="mt-2 text-3xl font-bold">Master material</h1></div>
<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.8fr]">
<section class="border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 text-lg font-semibold">Daftar material</h2>
<form method="POST" action="{{ route('projects.materials.store', $project) }}" class="space-y-3">@csrf
<input name="material_name" required placeholder="Nama material" class="w-full border border-slate-300 px-3 py-2 text-sm">
<input name="brand" placeholder="Merk" class="w-full border border-slate-300 px-3 py-2 text-sm">
<input name="unit" required placeholder="Satuan" class="w-full border border-slate-300 px-3 py-2 text-sm">
<input type="number" name="current_stock" step="0.01" placeholder="Stok awal" class="w-full border border-slate-300 px-3 py-2 text-sm">
<button class="w-full bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Simpan material</button></form>
<form method="GET" class="mt-8 flex gap-2"><input name="material_search" value="{{ request('material_search') }}" placeholder="Cari master material" class="min-w-0 flex-1 border border-slate-300 px-3 py-2 text-sm"><button class="bg-orange-600 px-3 py-2 text-sm font-semibold text-white">Cari</button></form>
<div class="mt-4 divide-y divide-slate-100">@forelse ($materials as $item)
<div class="flex items-center justify-between gap-2 py-3 text-sm"><a href="{{ route('projects.materials', [$project, 'material_id' => $item->id, 'month' => $month]) }}" class="min-w-0 flex-1 {{ $material?->id === $item->id ? 'font-semibold text-orange-600' : '' }}">{{ $item->material_name }} <small class="text-slate-400">{{ $item->brand ? '· '.$item->brand.' · ' : '· ' }}{{ $item->unit }}</small><strong class="ml-2">{{ $item->current_stock }}</strong></a>
<details><summary class="cursor-pointer text-orange-600">Edit</summary><form method="POST" action="{{ route('projects.materials.update', [$project, $item]) }}" class="mt-2 space-y-2">@csrf @method('PUT')<input name="material_name" value="{{ $item->material_name }}" required class="w-full border-slate-300 px-2 py-1 text-xs"><input name="brand" value="{{ $item->brand }}" placeholder="Merk" class="w-full border-slate-300 px-2 py-1 text-xs"><input name="unit" value="{{ $item->unit }}" required class="w-full border-slate-300 px-2 py-1 text-xs"><button class="bg-slate-900 px-2 py-1 text-xs text-white">Simpan</button></form><form method="POST" action="{{ route('projects.materials.destroy', [$project, $item]) }}" class="mt-2">@csrf @method('DELETE')<button class="text-xs text-red-600" onclick="return confirm('Hapus material ini?')">Hapus</button></form></details></div>
@empty <p class="py-4 text-sm text-slate-500">Belum ada material.</p> @endforelse</div>
</section>
<section class="border border-slate-200 bg-white p-5 shadow-sm">
<div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-semibold">{{ $material?->material_name ?: 'Pilih material' }}</h2><p class="text-sm text-slate-500">Periode {{ $month }}</p></div><form method="GET" class="flex flex-wrap gap-2"><input type="hidden" name="material_id" value="{{ $material?->id }}"><select name="month" onchange="this.form.submit()" class="border-slate-300 px-2 py-2 text-sm">@foreach ($months as $availableMonth)<option value="{{ $availableMonth }}" @selected($month === $availableMonth)>{{ \Carbon\Carbon::createFromFormat('Y-m', $availableMonth)->format('F Y') }}</option>@endforeach</select><input name="search" value="{{ request('search') }}" placeholder="Cari keperluan" class="w-36 border border-slate-300 px-3 py-2 text-sm"><button class="bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Filter</button></form></div>
@if ($material)
<form method="POST" action="{{ route('projects.materials.flows.store', $project) }}" class="mt-5 grid gap-3 sm:grid-cols-2">@csrf<input type="hidden" name="material_id" value="{{ $material->id }}"><input type="date" name="date" required class="border border-slate-300 px-3 py-2 text-sm"><input name="description" required placeholder="Keperluan" class="border border-slate-300 px-3 py-2 text-sm"><textarea name="detailed_description" placeholder="Deskripsi lengkap keluar/masuk barang" class="border border-slate-300 px-3 py-2 text-sm sm:col-span-2"></textarea><input name="result" placeholder="Hasil pekerjaan" class="border border-slate-300 px-3 py-2 text-sm sm:col-span-2"><input type="number" name="in_qty" step="0.01" min="0" placeholder="Masuk" class="border border-slate-300 px-3 py-2 text-sm"><input type="number" name="out_qty" step="0.01" min="0" placeholder="Keluar" class="border border-slate-300 px-3 py-2 text-sm"><button class="bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white sm:col-span-2">Catat flow material</button></form>
<div class="mt-6 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3">Keperluan</th><th class="px-3 py-3">Masuk</th><th class="px-3 py-3">Keluar</th><th class="px-3 py-3">Balance</th><th class="px-3 py-3">Detail</th><th class="px-3 py-3">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($flows as $flow)<tr><td class="px-3 py-3">{{ $flow->date->format('d M Y') }}</td><td class="px-3 py-3">{{ $flow->description }}</td><td class="px-3 py-3 text-emerald-600">{{ $flow->in_qty }}</td><td class="px-3 py-3 text-red-600">{{ $flow->out_qty }}</td><td class="px-3 py-3 font-semibold">{{ $flow->balance_qty }}</td><td class="px-3 py-3"><details><summary class="cursor-pointer text-orange-600">Lihat</summary><div class="mt-2 min-w-64 text-xs text-slate-600"><p><strong>Deskripsi:</strong> {{ $flow->detailed_description ?: '-' }}</p><p class="mt-1"><strong>Hasil:</strong> {{ $flow->result ?: '-' }}</p></div></details></td><td class="px-3 py-3"><details><summary class="cursor-pointer text-orange-600">Edit</summary><form method="POST" action="{{ route('projects.materials.flows.update', [$project, $flow]) }}" class="mt-2 grid gap-2">@csrf @method('PUT')<input type="date" name="date" value="{{ $flow->date->format('Y-m-d') }}" required><input name="description" value="{{ $flow->description }}" required><textarea name="detailed_description">{{ $flow->detailed_description }}</textarea><input name="result" value="{{ $flow->result }}" placeholder="Hasil pekerjaan"><input type="number" name="in_qty" value="{{ $flow->in_qty }}" step="0.01" min="0"><input type="number" name="out_qty" value="{{ $flow->out_qty }}" step="0.01" min="0"><button class="bg-slate-900 px-2 py-1 text-xs text-white">Simpan</button></form><form method="POST" action="{{ route('projects.materials.flows.destroy', [$project, $flow]) }}" class="mt-2">@csrf @method('DELETE')<button class="text-xs text-red-600" onclick="return confirm('Hapus transaksi ini?')">Hapus</button></form></details></td></tr>@empty<tr><td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">Tidak ada transaksi yang cocok.</td></tr>@endforelse</tbody></table></div>{{ $flows->links() }}
@endif
</section>
</div>
<div id="material-flow-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-6" role="dialog" aria-modal="true" aria-labelledby="material-flow-modal-title">
	<div class="w-full max-w-lg border border-slate-200 bg-white p-6 shadow-xl" onclick="event.stopPropagation()">
		<div class="flex items-center justify-between gap-4"><h2 id="material-flow-modal-title" class="text-lg font-semibold">Detail flow material</h2><button type="button" class="text-2xl text-slate-500 hover:text-slate-900" onclick="closeMaterialFlow()" aria-label="Tutup">&times;</button></div>
		<div class="mt-5 space-y-4 text-sm"><div><p class="font-semibold text-slate-500">Deskripsi lengkap</p><p id="material-flow-description" class="mt-1 whitespace-pre-wrap text-slate-800"></p></div><div><p class="font-semibold text-slate-500">Hasil</p><p id="material-flow-result" class="mt-1 whitespace-pre-wrap text-slate-800"></p></div></div>
	</div>
</div>
<script>
	const materialFlowModal = document.getElementById('material-flow-modal');
	const materialFlowDescription = document.getElementById('material-flow-description');
	const materialFlowResult = document.getElementById('material-flow-result');

	document.querySelectorAll('td details').forEach((detail) => {
		if (detail.querySelector('summary')?.textContent.trim() !== 'Lihat') return;
		const description = detail.querySelector('p:nth-of-type(1)')?.textContent.replace('Deskripsi:', '').trim() || '-';
		const result = detail.querySelector('p:nth-of-type(2)')?.textContent.replace('Hasil:', '').trim() || '-';
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'font-semibold text-orange-600 hover:text-orange-700';
		button.textContent = 'Detail';
		button.addEventListener('click', () => openMaterialFlow(description, result));
		detail.replaceWith(button);
	});

	function openMaterialFlow(description, result) {
		materialFlowDescription.textContent = description;
		materialFlowResult.textContent = result;
		materialFlowModal.classList.remove('hidden');
		materialFlowModal.classList.add('flex');
	}

	function closeMaterialFlow() {
		materialFlowModal.classList.add('hidden');
		materialFlowModal.classList.remove('flex');
	}

	materialFlowModal.addEventListener('click', closeMaterialFlow);
</script>
@endsection
