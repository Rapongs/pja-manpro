@extends('layouts.app', ['title' => $supplier->name])

@php
    $waNumber = preg_replace('/[^0-9]/', '', $supplier->phone ?? '');
    if (strlen($waNumber) >= 10) {
        if (str_starts_with($waNumber, '0')) {
            $waNumber = '62'.substr($waNumber, 1);
        } elseif (str_starts_with($waNumber, '8')) {
            $waNumber = '62'.$waNumber;
        }
    }
    $template = "Halo *{$supplier->name}*, saya ingin memesan bahan-bahan berikut:" . PHP_EOL . PHP_EOL;
    $template .= "1. (tulis material di sini)" . PHP_EOL;
    $template .= "2. (dst.)" . PHP_EOL . PHP_EOL;
    $template .= "Untuk pengiriman diantar ke alamat: (tulis alamat pengiriman di sini).";
    $waMessage = urlencode($template);
    $waUrl = $waNumber ? "https://wa.me/{$waNumber}?text={$waMessage}" : '#';
@endphp

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-center md:items-end">
<div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Detail supplier</p><h1 class="mt-2 text-3xl font-bold">{{ $supplier->name }}</h1><p class="mt-2 text-slate-500">{{ $supplier->phone }} · {{ $supplier->address }}</p></div>
@if ($waNumber)
<a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
Chat WhatsApp
</a>
@endif
</div>

<div class="mt-8">
<a href="{{ route('procurements.index') }}" class="text-sm text-orange-600 hover:underline">&larr; Kembali ke pengadaan</a>
</div>

@if (session('success'))
<div class="mt-4 border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mt-6 border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="text-lg font-semibold">Riwayat pengadaan {{ $supplier->name }}</h2>
<p class="mt-1 text-sm text-slate-500">{{ $supplier->procurements_count ?? $supplier->procurements->count() }} transaksi</p>
<div class="mt-5 space-y-5">@forelse ($supplier->procurements as $procurement)<article class="border-t border-slate-200 pt-4"><div class="flex flex-wrap justify-between gap-3 text-sm"><div><span class="font-semibold">{{ $procurement->date->format('d M Y') }}</span> · {{ $procurement->project->name }}</div><strong>Rp {{ number_format($procurement->total_price, 0, ',', '.') }}</strong></div><ul class="mt-2 list-disc pl-5 text-sm text-slate-600">@foreach ($procurement->items as $item)<li>{{ $item->material_name }}{{ $item->brand ? ' · '.$item->brand : '' }}: {{ $item->quantity }} {{ $item->unit }} × Rp {{ number_format($item->price, 0, ',', '.') }}</li>@endforeach</ul></article>@empty<p class="text-sm text-slate-500">Belum ada riwayat pengadaan untuk supplier ini.</p>@endforelse</div>
</div>

<div class="mt-6 border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="text-lg font-semibold">Template pesan WhatsApp</h2>
<p class="mt-1 text-sm text-slate-500">Salin teks berikut, atau gunakan tombol Chat WhatsApp di atas untuk membuka WA dengan pesan yang sudah terisi.</p>
<textarea id="wa-template" readonly class="mt-3 w-full border border-slate-300 bg-slate-50 px-3 py-2 text-sm" rows="8">{{ trim($template) }}</textarea>
<div class="mt-2 flex gap-2"><button type="button" id="copy-template" class="border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-orange-50">Salin teks</button>@if ($waNumber)<a href="{{ $waUrl }}" target="_blank" rel="noopener" class="bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Buka WhatsApp</a>@endif</div>
</div>

<script>
document.getElementById('copy-template')?.addEventListener('click', async () => {
    const textarea = document.getElementById('wa-template');
    await navigator.clipboard.writeText(textarea.value);
    const btn = document.getElementById('copy-template');
    const old = btn.textContent;
    btn.textContent = 'Tersalin!';
    setTimeout(() => { btn.textContent = old; }, 1500);
});
</script>
@endsection