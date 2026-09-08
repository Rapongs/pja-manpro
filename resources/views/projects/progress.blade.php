@extends('layouts.app', ['title' => 'Progress · '.$project->name])

@section('content')
<a href="{{ route('projects.dashboard', $project) }}" class="text-sm font-semibold text-orange-600">&larr; Dashboard proyek</a>
<div class="mt-5 flex items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Progress proyek</p><h1 class="mt-2 text-3xl font-bold">Kurva S & Lapjusik</h1></div></div>

@if (! session('guest_mode', false))
<section class="guest-mutation-section mt-8 border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 text-lg font-semibold">Upload file Excel progress</h2>
<p class="mb-4 text-sm text-slate-500">Unggah file Excel. Setelah unggah, Anda dapat memilih sheet dan menentukan posisi sel (baris & kolom) untuk label minggu, target, dan realisasi sesuai format file Anda.</p>
<form method="POST" action="{{ route('projects.progress.import', $project) }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
@csrf
<input type="file" name="progress_file" accept=".xlsx" required class="w-full border border-slate-300 px-3 py-2 text-sm">
<button class="w-full bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 sm:w-auto">Upload & lanjutkan</button>
</form>
@if ($import)
<p class="mt-3 text-sm text-slate-500">File terakhir: <strong>{{ $import->source_filename }}</strong> · {{ $import->imported_at->format('d M Y H:i') }} · Sheet: {{ $import->sheet_name ?? '-' }}
@if ($import->cell_config)
    · Konfigurasi: baris label {{ $import->cell_config['week_row'] }} (kol {{ $import->cell_config['week_start_col'] }}–{{ $import->cell_config['week_end_col'] }}), target baris {{ $import->cell_config['target_row'] }}, realisasi baris {{ $import->cell_config['actual_row'] }}
@endif
</p>
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
#excel-live-view tbody td.lv-rownum, #excel-live-view thead th.lv-corner { position: sticky; left: 0; text-align: center; background: #f1f5f9; z-index: 2; }
#excel-live-view th { background: #f1f5f9; font-weight: 600; text-align: center; }
#excel-live-view td.lv-week-row { background: #ffedd5; font-weight: 700; }
#excel-live-view td.lv-target-row { background: #fecaca; font-weight: 700; }
#excel-live-view td.lv-actual-row { background: #bbf7d0; font-weight: 700; }
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
    const cfg = @json($import->cell_config ?? null);
    if (!container) return;

    function colNumber(letter) {
        let n = 0;
        String(letter || '').toUpperCase().replace(/[^A-Z]/g, '').split('').forEach(function (c) { n = n * 26 + c.charCodeAt(0) - 64; });
        return n;
    }

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

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function colLetter(n) {
        let s = '';
        while (n > 0) { s = String.fromCharCode(65 + ((n - 1) % 26)) + s; n = Math.floor((n - 1) / 26); }
        return s;
    }

    function cellText(ws, r, c) {
        const cell = ws[XLSX.utils.encode_cell({ r: r, c: c })];
        if (!cell) return '';
        if (cell.w !== undefined && cell.w !== null) return String(cell.w);
        if (cell.v === undefined || cell.v === null) return '';
        return String(cell.v);
    }

    function buildSheetHtml(wb, name) {
        const ws = wb.Sheets[name];
        if (!ws) return '';
        const range = XLSX.utils.decode_range(ws['!ref'] || 'A1');

        let startRow = range.s.r;
        let endRow = range.e.r;
        if (cfg) {
            const sr = parseInt(cfg.start_row, 10) || 0;
            const er = parseInt(cfg.end_row, 10) || 0;
            if (sr > range.s.r) startRow = Math.min(sr - 1, range.e.r);
            if (er > 0) endRow = Math.min(er - 1, range.e.r);
            if (endRow < startRow) endRow = startRow;
        }

        const merges = {};
        (ws['!merges'] || []).forEach(function (m) {
            for (let rr = m.s.r; rr <= m.e.r; rr++) {
                for (let cc = m.s.c; cc <= m.e.c; cc++) {
                    merges[rr + ':' + cc] = { r: m.s.r, c: m.s.c, rowspan: m.e.r - m.s.r + 1, colspan: m.e.c - m.s.c + 1 };
                }
            }
        });

        let html = '<table>';
        if (ws['!cols']) html += '<colgroup>';
        for (let c = range.s.c; c <= range.e.c; c++) {
            const col = ws['!cols'] ? ws['!cols'][c] : null;
            const w = col && col.wpx ? Math.max(col.wpx, 40) : 80;
            html += '<col style="width:' + w + 'px">';
        }
        if (ws['!cols']) html += '</colgroup>';

        html += '<thead><tr><th class="lv-corner"></th>';
        for (let c = range.s.c; c <= range.e.c; c++) html += '<th>'+colLetter(c+1)+'</th>';
        html += '</tr></thead><tbody>';

        for (let r = startRow; r <= endRow; r++) {
            html += '<tr><td class="lv-rownum">' + (r + 1) + '</td>';
            for (let c = range.s.c; c <= range.e.c; c++) {
                const m = merges[r + ':' + c];
                if (m && (m.r !== r || m.c !== c)) continue;
                const text = cellText(ws, r, c);
                let cls = 'lv-cell';
                if (cfg) {
                    const weekRow = parseInt(cfg.week_row, 10);
                    const targetRow = parseInt(cfg.target_row, 10);
                    const actualRow = parseInt(cfg.actual_row, 10);
                    const weekStart = cfg.week_start_col ? colNumber(cfg.week_start_col) : 0;
                    const weekEnd = cfg.week_end_col ? colNumber(cfg.week_end_col) : 0;
                    if (weekRow === r + 1 && c + 1 >= weekStart && c + 1 <= weekEnd) cls += ' lv-week-row';
                    else if (targetRow === r + 1) cls += ' lv-target-row';
                    else if (actualRow === r + 1) cls += ' lv-actual-row';
                }
                const span = m && m.r === r && m.c === c
                    ? ' colspan="' + m.colspan + '" rowspan="' + m.rowspan + '"'
                    : '';
                html += '<td class="' + cls + '"' + span + ' data-r="' + (r + 1) + '" data-c="' + (c + 1) + '" title="' + escHtml(text) + '">' + escHtml(text) + '</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        return html;
    }

    function showSheet(wb, name) {
        const html = buildSheetHtml(wb, name);
        if (!html) return;
        container.innerHTML = html;

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
