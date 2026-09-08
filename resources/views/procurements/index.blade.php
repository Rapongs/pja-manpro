@extends('layouts.app', ['title' => 'Pengadaan Material'])

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Pengadaan global</p><h1 class="mt-2 text-3xl font-bold">Pengadaan material</h1><p class="mt-2 text-slate-500">Supplier dan riwayat pembelian seluruh project.</p></div></div>
<div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.8fr]">
<section class="guest-mutation-section border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 text-lg font-semibold">Catat pengadaan</h2>
<form method="POST" action="{{ route('procurements.store') }}" class="space-y-3">@csrf
<label class="block text-sm font-medium">Nama supplier
<select id="supplier-select" name="supplier_id" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm">
<option value="">-- Pilih supplier --</option>
@foreach ($suppliers as $item)
<option value="{{ $item->id }}" data-phone="{{ $item->phone }}" data-address="{{ $item->address }}">{{ $item->name }}</option>
@endforeach
<option value="new">+ Tambah supplier baru</option>
</select>
</label>
<div id="new-supplier-fields" class="hidden space-y-3">
<label class="block text-sm font-medium">Nama supplier baru<input type="text" name="supplier_name" placeholder="Nama supplier" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<label class="block text-sm font-medium">Nomor telepon<input type="text" name="phone" placeholder="Nomor telepon" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<label class="block text-sm font-medium">Alamat supplier<textarea name="address" placeholder="Alamat supplier" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></textarea></label>
</div>
<div id="existing-supplier-info" class="hidden rounded border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
<p><strong>Telepon:</strong> <span id="info-phone"></span></p>
<p><strong>Alamat:</strong> <span id="info-address"></span></p>
</div>
<label class="block text-sm font-medium">Project<select name="project_id" required class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"><option value="">Pilih project</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">Tanggal pembelian<input type="date" name="date" required value="{{ now()->format('Y-m-d') }}" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<label class="block text-sm font-medium">PIC penerima<input type="text" name="receiver_pic" required placeholder="Nama yang menerima barang" class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm"></label>
<div id="procurement-items" class="space-y-3"><div class="procurement-item border-t border-slate-200 pt-3"><div class="grid gap-2 sm:grid-cols-2"><label class="text-sm font-medium">Nama material<input name="items[0][material_name]" required placeholder="Nama material" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Merk<input name="items[0][brand]" placeholder="Merk" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Satuan<input name="items[0][unit]" required placeholder="Satuan" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Kuantitas<input type="number" name="items[0][quantity]" required min="0.01" step="0.01" placeholder="Kuantitas" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label><label class="text-sm font-medium">Harga satuan<input type="number" name="items[0][price]" required min="0" step="0.01" placeholder="Harga satuan" class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm"></label></div></div></div>
<button type="button" id="add-item" class="w-full border border-slate-300 px-3 py-2 text-sm font-semibold">Tambah barang</button><button class="w-full bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan pengadaan</button>
</form>
</section>
<section class="border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Daftar supplier</h2><div class="divide-y divide-slate-100">@forelse ($suppliers as $item)<a href="{{ route('procurements.supplier', $item->id) }}" class="block py-4 hover:bg-orange-50"><p class="font-semibold">{{ $item->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $item->phone }} · {{ $item->procurements_count }} pengadaan</p></a>@empty<p class="text-sm text-slate-500">Belum ada supplier.</p>@endforelse</div></section>
</div>
@if ($supplier)<section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-semibold">Riwayat {{ $supplier->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $supplier->phone }} · {{ $supplier->address }}</p><div class="mt-5 space-y-5">@foreach ($supplier->procurements as $procurement)<article class="border-t border-slate-200 pt-4"><div class="flex justify-between gap-3 text-sm"><span>{{ $procurement->date->format('d M Y') }} · {{ $procurement->project->name }}</span><strong>Rp {{ number_format($procurement->total_price, 0, ',', '.') }}</strong></div><p class="mt-1 text-xs text-slate-500">PIC penerima: {{ $procurement->receiver_pic ?? '-' }}</p><ul class="mt-2 list-disc pl-5 text-sm text-slate-600">@foreach ($procurement->items as $item)<li>{{ $item->material_name }}{{ $item->brand ? ' · '.$item->brand : '' }}: {{ $item->quantity }} {{ $item->unit }} × Rp {{ number_format($item->price, 0, ',', '.') }}</li>@endforeach</ul></article>@endforeach</div></section>@endif
<script>
const supplierSelect = document.getElementById('supplier-select');
const newSupplierFields = document.getElementById('new-supplier-fields');
const existingSupplierInfo = document.getElementById('existing-supplier-info');
const infoPhone = document.getElementById('info-phone');
const infoAddress = document.getElementById('info-address');

supplierSelect?.addEventListener('change', function() {
    if (this.value === 'new') {
        newSupplierFields.classList.remove('hidden');
        existingSupplierInfo.classList.add('hidden');
        newSupplierFields.querySelectorAll('input').forEach(i => i.required = true);
    } else if (this.value) {
        const opt = this.options[this.selectedIndex];
        infoPhone.textContent = opt.dataset.phone || '-';
        infoAddress.textContent = opt.dataset.address || '-';
        existingSupplierInfo.classList.remove('hidden');
        newSupplierFields.classList.add('hidden');
        newSupplierFields.querySelectorAll('input').forEach(i => { i.required = false; i.value = ''; });
    } else {
        existingSupplierInfo.classList.add('hidden');
        newSupplierFields.classList.add('hidden');
        newSupplierFields.querySelectorAll('input').forEach(i => { i.required = false; i.value = ''; });
    }
});

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
