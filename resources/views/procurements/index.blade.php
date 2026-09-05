@extends('layouts.app', ['title' => 'Pengadaan Material'])

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Pengadaan global</p><h1 class="mt-2 text-3xl font-bold">Pengadaan material</h1><p class="mt-2 text-slate-500">Supplier dan riwayat pembelian seluruh project.</p></div></div>
<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.8fr]">
<section class="guest-mutation-section border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 text-lg font-semibold">Catat pengadaan</h2>
<form method="POST" action="{{ route('procurements.store') }}" class="space-y-3">@csrf
<label class="block text-sm font-medium">Nama supplier<input name="supplier_name" required placeholder="Nama supplier" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<label class="block text-sm font-medium">Nomor telepon<input name="phone" required placeholder="Nomor telepon" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<label class="block text-sm font-medium">Alamat supplier<textarea name="address" required placeholder="Alamat supplier" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></textarea></label>
<label class="block text-sm font-medium">Project<select name="project_id" required class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"><option value="">Pilih project</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">Tanggal pembelian<input type="date" name="date" required value="{{ now()->format('Y-m-d') }}" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<div id="procurement-items" class="space-y-3"><div class="procurement-item border-t border-slate-200 pt-3"><div class="grid gap-2 sm:grid-cols-2"><label class="text-sm font-medium">Nama material<input name="items[0][material_name]" required placeholder="Nama material" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Merk<input name="items[0][brand]" placeholder="Merk" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Satuan<input name="items[0][unit]" required placeholder="Satuan" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Kuantitas<input type="number" name="items[0][quantity]" required min="0.01" step="0.01" placeholder="Kuantitas" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Harga satuan<input type="number" name="items[0][price]" required min="0" step="0.01" placeholder="Harga satuan" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label></div></div></div>
<button type="button" id="add-item" class="w-full border border-slate-300 px-3 py-2 text-sm font-semibold">Tambah barang</button><button class="w-full bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan pengadaan</button>
</form>
</section>
<section class="border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Daftar supplier</h2><div class="divide-y divide-slate-100">@forelse ($suppliers as $item)<a href="{{ route('procurements.index', ['supplier_id' => $item->id]) }}" class="block py-4 hover:bg-orange-50"><p class="font-semibold">{{ $item->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $item->phone }} · {{ $item->procurements_count }} pengadaan</p></a>@empty<p class="text-sm text-slate-500">Belum ada supplier.</p>@endforelse</div></section>
</div>
@if ($supplier)<section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-semibold">Riwayat {{ $supplier->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $supplier->phone }} · {{ $supplier->address }}</p><div class="mt-5 space-y-5">@foreach ($supplier->procurements as $procurement)<article class="border-t border-slate-200 pt-4"><div class="flex justify-between gap-3 text-sm"><span>{{ $procurement->date->format('d M Y') }} · {{ $procurement->project->name }}</span><strong>Rp {{ number_format($procurement->total_price, 0, ',', '.') }}</strong></div><ul class="mt-2 list-disc pl-5 text-sm text-slate-600">@foreach ($procurement->items as $item)<li>{{ $item->material_name }}{{ $item->brand ? ' · '.$item->brand : '' }}: {{ $item->quantity }} {{ $item->unit }} × Rp {{ number_format($item->price, 0, ',', '.') }}</li>@endforeach</ul></article>@endforeach</div></section>@endif
<script>
let itemIndex = 1;
document.getElementById('add-item')?.addEventListener('click', () => {
    const item = document.createElement('div');
    item.className = 'procurement-item border-t border-slate-200 pt-3';
    item.innerHTML = `<div class="grid gap-2 sm:grid-cols-2"><label class="text-sm font-medium">Nama material<input name="items[${itemIndex}][material_name]" required placeholder="Nama material" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Merk<input name="items[${itemIndex}][brand]" placeholder="Merk" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Satuan<input name="items[${itemIndex}][unit]" required placeholder="Satuan" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Kuantitas<input type="number" name="items[${itemIndex}][quantity]" required min="0.01" step="0.01" placeholder="Kuantitas" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Harga satuan<input type="number" name="items[${itemIndex}][price]" required min="0" step="0.01" placeholder="Harga satuan" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label></div>`;
    document.getElementById('procurement-items').appendChild(item);
    itemIndex++;
});
</script>
@endsection
