@extends('layouts.app', ['title' => 'Progress · '.$project->name])

@section('content')
<a href="{{ route('projects.dashboard', $project) }}" class="text-sm font-semibold text-orange-600">&larr; Dashboard proyek</a>
<div class="mt-5 flex items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Progress proyek</p><h1 class="mt-2 text-3xl font-bold">Kurva S & Lapjusik</h1></div></div>

@if (! session('guest_mode', false))
<section class="guest-mutation-section mt-8 border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 text-lg font-semibold">Upload file Excel progress</h2>
<p class="mb-4 text-sm text-slate-500">Unggah file Excel untuk membaca otomatis target (baris 36) dan realisasi (baris 38) beserta tabel rincian (baris 9–39).</p>
<form method="POST" action="{{ route('projects.progress.import', $project) }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
@csrf
<input type="file" name="progress_file" accept=".xlsx" required class="w-full border border-slate-300 px-3 py-2 text-sm">
<button class="w-full bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 sm:w-auto">Upload & impor</button>
</form>
@if ($import)
<p class="mt-3 text-sm text-slate-500">File terakhir: <strong>{{ $import->source_filename }}</strong> · {{ $import->imported_at->format('d M Y H:i') }} · Sheet: {{ $import->sheet_name ?? '-' }}</p>
@endif
</section>
@endif

@if ($import)
<div class="progress-overview mt-8 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
    <section class="progress-chart border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Rencana vs aktual mingguan</h2><canvas id="progress-chart" height="150"></canvas></section>
    <section class="border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Ringkasan import</h2><div class="space-y-2 text-sm text-slate-600"><p><strong>Minggu terdeteksi:</strong> {{ count($weekLabels) }}</p><p><strong>Target akhir:</strong> {{ end($chartPlanned) !== false ? number_format((float) end($chartPlanned), 2, ',', '.') : '-' }}%</p><p><strong>Realisasi akhir:</strong> {{ $latestActual !== null ? number_format($latestActual, 2, ',', '.') . '%' : '-' }}</p></div></section>
</div>

<section class="mt-8 border border-slate-200 bg-white p-5 shadow-sm">
<div class="mb-3 flex flex-wrap items-center justify-between gap-3">
<h2 class="text-lg font-semibold">Live view Excel</h2>
<div class="flex items-center gap-3">
<span class="text-xs text-slate-400">{{ $import->source_filename }}</span>
<a href="{{ route('projects.progress.source', $project) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-orange-600 hover:underline">Buka file asli &nearr;</a>
</div>
</div>
<div id="excel-sheet-tabs" class="mb-3 flex flex-wrap gap-1"></div>
<div id="excel-live-view" class="overflow-x-auto border border-slate-300 bg-white"><p class="px-3 py-4 text-sm text-slate-500">Memuat file Excel…</p></div>
<style>
#excel-live-view table { border-collapse: collapse; width: max-content; min-width: 100%; }
#excel-live-view td, #excel-live-view th { border: 1px solid #e2e8f0; padding: 4px 8px; font-size: 12px; white-space: nowrap; }
#excel-live-view tbody td:first-child, #excel-live-view thead th:first-child { background: #f8fafc; font-weight: 600; }
#excel-live-view th { background: #f1f5f9; font-weight: 600; text-align: left; }
</style>
</section>
@else
<div class="progress-overview mt-8 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
    <section class="progress-chart border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Rencana vs aktual mingguan</h2><canvas id="progress-chart" height="150"></canvas></section>
    <section class="border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Belum ada data</h2><p class="text-sm text-slate-500">Upload file Excel di atas untuk menampilkan grafik kurva S.</p></section>
</div>
@endif

@if ($import)
<div class="progress-tables mt-6 grid gap-6 lg:grid-cols-2">
<section class="overflow-hidden border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 p-5"><h2 class="text-lg font-semibold">Target Kurva S Mingguan</h2></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Minggu</th><th class="px-5 py-3">Target</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($weekLabels as $i => $wlabel)<tr><td class="px-5 py-3">{{ $wlabel }}</td><td class="px-5 py-3">{{ isset($chartPlanned[$i]) && $chartPlanned[$i] !== null ? number_format((float) $chartPlanned[$i], 2, ',', '.') . '%' : '-' }}</td></tr>@endforeach</tbody></table></div></section>
<section class="overflow-hidden border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 p-5"><h2 class="text-lg font-semibold">Laporan kemajuan fisik mingguan</h2></div><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Minggu</th><th class="px-5 py-3">Progress</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($weekLabels as $i => $wlabel)<tr><td class="px-5 py-3">{{ $wlabel }}</td><td class="px-5 py-3">{{ isset($chartActual[$i]) && $chartActual[$i] !== null ? number_format((float) $chartActual[$i], 2, ',', '.') . '%' : '-' }}</td></tr>@endforeach</tbody></table></div></section>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>new Chart(document.getElementById('progress-chart'), {type:'line', data:{labels:@json($chartLabels), datasets:[{label:'Rencana', data:@json($chartPlanned), borderColor:'#f97316', borderWidth:2, pointRadius:3, tension:.3, spanGaps:true},{label:'Aktual', data:@json($chartActual), borderColor:'#0f172a', borderWidth:2, pointRadius:3, tension:.3, spanGaps:false}]}, options:{responsive:true, scales:{y:{min:0,max:100}}}});</script>
@if ($import)
<script>
(function () {
    const container = document.getElementById('excel-live-view');
    const tabs = document.getElementById('excel-sheet-tabs');
    const defaultSheet = @json($import->sheet_name ?? '');
    if (!container) return;

    function loadSheetJS() {
        return new Promise(function (resolve, reject) {
            if (window.XLSX) return resolve();
            const s = document.createElement('script');
            s.src = 'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js';
            s.onload = resolve;
            s.onerror = function () { reject(new Error('Gagal memuat library xlsx')); };
            document.head.appendChild(s);
        });
    }

    function showSheet(wb, name) {
        const ws = wb.Sheets[name];
        if (!ws) return;
        const html = XLSX.utils.sheet_to_html(ws, { id: 'excel-sheet-table' });
        container.innerHTML = html;
        const table = container.querySelector('table');
        if (!table) return;

        if (ws['!cols'] && ws['!cols'].length) {
            const colgroup = document.createElement('colgroup');
            ws['!cols'].forEach(function (col) {
                const el = document.createElement('col');
                el.style.width = Math.max(col.wpx || 80, 40) + 'px';
                colgroup.appendChild(el);
            });
            table.insertBefore(colgroup, table.firstChild);
        }

        Array.from(tabs.children).forEach(function (btn) {
            btn.classList.toggle('border-orange-600', btn.dataset.sheet === name);
            btn.classList.toggle('text-orange-600', btn.dataset.sheet === name);
            btn.classList.toggle('bg-orange-50', btn.dataset.sheet === name);
        });
    }

    async function render() {
        container.innerHTML = '<p class="px-3 py-4 text-sm text-slate-500">Memuat file Excel…</p>';
        try {
            await loadSheetJS();
            const buf = await (await fetch(@json(route('projects.progress.source', $project)))).arrayBuffer();
            const wb = XLSX.read(buf, { cellStyles: true, cellNF: true });
            const target = defaultSheet && wb.SheetNames.includes(defaultSheet) ? defaultSheet : wb.SheetNames[0];

            tabs.innerHTML = '';
            wb.SheetNames.forEach(function (name) {
                const b = document.createElement('button');
                b.type = 'button';
                b.dataset.sheet = name;
                b.textContent = name;
                b.className = 'border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-orange-50';
                b.addEventListener('click', function () { showSheet(wb, name); });
                tabs.appendChild(b);
            });

            showSheet(wb, target);
        } catch (e) {
            container.innerHTML = '<p class="px-3 py-4 text-sm text-red-600">Gagal menampilkan file Excel: ' + (e && e.message ? e.message : 'terjadi kesalahan') + '</p>';
        }
    }

    render();
})();
</script>
@endif
@endsection
