@extends('layouts.app', ['title' => 'Konfigurasi Import · '.$project->name])

@php
    $defaultSheet = collect($sheets)->firstWhere('name', 'TS (10)')['name']
        ?? ($sheets[0]['name'] ?? '');
    $oldOr = fn ($key, $fallback) => old($key, $fallback);
@endphp

@section('content')
<a href="{{ route('projects.progress', $project) }}" class="text-sm font-semibold text-orange-600">&larr; Kembali ke progress</a>
<div class="mt-5 flex items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Import progress</p><h1 class="mt-2 text-3xl font-bold">Konfigurasi posisi sel</h1></div></div>

<section class="mt-8 border border-slate-200 bg-white p-5 shadow-sm">
<p class="text-sm text-slate-500">File: <strong>{{ session('pending_import_filename') }}</strong>. Tentukan sheet dan posisi baris/kolom pada file tersebut, lalu klik <strong>Impor sekarang</strong>. Gunakan diagram di bawah sebagai panduan.</p>
<form method="POST" action="{{ route('projects.progress.import.store', $project) }}" class="mt-6 grid gap-x-8 gap-y-4 lg:grid-cols-2">
@csrf
<div>
<label class="form-field-label" for="sheet_name">Sheet</label>
<select id="sheet_name" name="sheet_name" class="w-full border border-slate-300 px-3 py-2 text-sm">
@foreach ($sheets as $s)
<option value="{{ $s['name'] }}" @selected(old('sheet_name', $defaultSheet) === $s['name'])>{{ $s['name'] }}</option>
@endforeach
</select>
</div>
<div>
<label class="form-field-label">Rentang baris tabel rincian (dari–sampai)</label>
<div class="flex items-center gap-2">
<input id="table_range_start" type="number" name="start_row" min="1" value="{{ $oldOr('start_row', 9) }}" class="w-full border border-slate-300 px-3 py-2 text-sm">
<span class="text-slate-400">&ndash;</span>
<input id="table_range_end" type="number" name="end_row" min="1" value="{{ $oldOr('end_row', 39) }}" class="w-full border border-slate-300 px-3 py-2 text-sm">
</div>
</div>
<div>
<label class="form-field-label" for="week_row">Baris label minggu</label>
<input id="week_row" type="number" name="week_row" min="1" value="{{ $oldOr('week_row', 11) }}" class="w-full border border-slate-300 px-3 py-2 text-sm">
</div>
<div>
<label class="form-field-label" for="week_range">Kolom label minggu (dari–sampai)</label>
<div class="flex items-center gap-2">
<input id="week_start_col" type="text" name="week_start_col" maxlength="3" value="{{ $oldOr('week_start_col', 'F') }}" class="w-full uppercase border border-slate-300 px-3 py-2 text-sm">
<span class="text-slate-400">&ndash;</span>
<input id="week_end_col" type="text" name="week_end_col" maxlength="3" value="{{ $oldOr('week_end_col', 'AA') }}" class="w-full uppercase border border-slate-300 px-3 py-2 text-sm">
</div>
</div>
<div>
<label class="form-field-label" for="target_row">Baris nilai target (rencana)</label>
<input id="target_row" type="number" name="target_row" min="1" value="{{ $oldOr('target_row', 36) }}" class="w-full border border-slate-300 px-3 py-2 text-sm">
</div>
<div>
<label class="form-field-label" for="actual_row">Baris nilai realisasi</label>
<input id="actual_row" type="number" name="actual_row" min="1" value="{{ $oldOr('actual_row', 38) }}" class="w-full border border-slate-300 px-3 py-2 text-sm">
</div>
<div class="lg:col-span-2 flex items-center justify-end gap-3">
<button type="submit" class="bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Impor sekarang</button>
</div>
</form>
</section>

<section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-1 text-lg font-semibold">Pratinjau isi file</h2>
<p class="mb-4 text-sm text-slate-500">Sel yang memuat label minggu, target, dan realisasi otomatis disorot sesuai input di atas.</p>
<div id="preview-tabs" class="mb-3 flex flex-wrap gap-1"></div>
<div id="preview-grid-wrap" class="overflow-x-auto border border-slate-300 bg-white"></div>
<style>
#preview-grid-wrap table { border-collapse: collapse; width: max-content; min-width: 100%; }
#preview-grid-wrap td, #preview-grid-wrap th { border: 1px solid #e2e8f0; padding: 4px 8px; font-size: 11px; white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis; }
#preview-grid-wrap tbody td.pr-row, #preview-grid-wrap thead th.pr-row { background: #ffedd5; font-weight: 600; }
#preview-grid-wrap td.pr-col-range, #preview-grid-wrap thead th.pr-col-range { background: #ffedd5; }
#preview-grid-wrap td.pr-target, #preview-grid-wrap td.pr-actual { font-weight: 700; }
#preview-grid-wrap td.pr-target { background: #fecaca; }
#preview-grid-wrap td.pr-actual { background: #bbf7d0; }
#preview-grid-wrap td.pr-corner { background: #fff7ed; }
</style>
</section>

<script>
(function () {
    const SHEETS = @json(collect($sheets)->map(function ($sheet) {
        return ['name' => $sheet['name'], 'rows' => $sheet['rows']];
    })->values()->all());
    const tabsEl = document.getElementById('preview-tabs');
    const gridWrap = document.getElementById('preview-grid-wrap');
    const fields = {
        startRow: document.getElementById('start_row'),
        endRow: document.getElementById('end_row'),
        weekRow: document.getElementById('week_row'),
        weekStart: document.getElementById('week_start_col'),
        weekEnd: document.getElementById('week_end_col'),
        targetRow: document.getElementById('target_row'),
        actualRow: document.getElementById('actual_row'),
        sheet: document.getElementById('sheet_name'),
    };

    function colNumber(letter) {
        let n = 0;
        String(letter || '').toUpperCase().replace(/[^A-Z]/g, '').split('').forEach(function (c) { n = n * 26 + c.charCodeAt(0) - 64; });
        return n;
    }

    function colLetter(n) {
        let s = '';
        while (n > 0) { s = String.fromCharCode(65 + ((n - 1) % 26)) + s; n = Math.floor((n - 1) / 26); }
        return s;
    }

    function highlight() {
        const rows = document.querySelectorAll('#preview-grid-wrap tbody tr');
        const startRow = parseInt(fields.startRow.value, 10) || 0;
        const endRow = parseInt(fields.endRow.value, 10) || 0;
        const weekRow = parseInt(fields.weekRow.value, 10) || 0;
        const weekStart = colNumber(fields.weekStart.value) || 0;
        const weekEnd = colNumber(fields.weekEnd.value) || 0;
        const targetRow = parseInt(fields.targetRow.value, 10) || 0;
        const actualRow = parseInt(fields.actualRow.value, 10) || 0;

        rows.forEach(function (tr) {
            const r = parseInt(tr.dataset.r, 10);
            const cells = Array.from(tr.querySelectorAll('td.pr-cell'));
            cells.forEach(function (td) {
                const c = parseInt(td.dataset.c, 10);
                td.classList.remove('pr-row', 'pr-col-range', 'pr-target', 'pr-actual');
                const inRange = startRow && endRow && r >= startRow && r <= endRow && c >= weekStart && c <= weekEnd && weekStart && weekEnd;
                if (r === weekRow && c >= weekStart && c <= weekEnd && weekStart) td.classList.add('pr-row');
                else if (inRange) td.classList.add('pr-col-range');
                if (r === targetRow) td.classList.add('pr-target');
                if (r === actualRow) td.classList.add('pr-actual');
            });
        });
    }

    function renderSheet(name) {
        const sheet = SHEETS.find(function (s) { return s.name === name; }) || SHEETS[0];
        if (!sheet || !sheet.rows) { gridWrap.innerHTML = '<p class="px-3 py-4 text-sm text-slate-500">Tidak ada baris yang terbaca untuk sheet ini.</p>'; return; }
        const rowNumbers = Object.keys(sheet.rows).map(Number).sort(function (a, b) { return a - b; });
        let maxCol = 1;
        rowNumbers.forEach(function (r) { Object.keys(sheet.rows[r]).forEach(function (c) { maxCol = Math.max(maxCol, parseInt(c, 10)); }); });

        let html = '<table><thead><tr><th class="pr-corner"></th>';
        for (let c = 1; c <= maxCol; c++) html += '<th data-c="' + c + '">' + colLetter(c) + '</th>';
        html += '</tr></thead><tbody>';
        rowNumbers.forEach(function (r) {
            html += '<tr data-r="' + r + '"><td class="pr-row pr-corner">' + r + '</td>';
            for (let c = 1; c <= maxCol; c++) {
                const v = sheet.rows[r][c];
                const text = (v === null || v === undefined) ? '' : String(v);
                html += '<td class="pr-cell" data-c="' + c + '" title="' + text.replace(/"/g, '&quot;') + '">' + (text ? text.replace(/</g, '&lt;') : '') + '</td>';
            }
            html += '</tr>';
        });
        html += '</tbody></table>';
        gridWrap.innerHTML = html;
        Array.from(tabsEl.children).forEach(function (b) {
            b.classList.toggle('border-orange-600', b.dataset.sheet === name);
            b.classList.toggle('text-orange-600', b.dataset.sheet === name);
            b.classList.toggle('bg-orange-50', b.dataset.sheet === name);
        });
        highlight();
    }

    function buildTabs() {
        tabsEl.innerHTML = '';
        SHEETS.forEach(function (s) {
            const b = document.createElement('button');
            b.type = 'button';
            b.dataset.sheet = s.name;
            b.textContent = s.name;
            b.className = 'border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-orange-50';
            b.addEventListener('click', function () { fields.sheet.value = s.name; renderSheet(s.name); });
            tabsEl.appendChild(b);
        });
    }

    Object.values(fields).forEach(function (f) { if (f) f.addEventListener('input', highlight); });
    fields.sheet.addEventListener('change', function () { renderSheet(fields.sheet.value); });
    buildTabs();
    renderSheet(fields.sheet.value);
})();
</script>
@endsection