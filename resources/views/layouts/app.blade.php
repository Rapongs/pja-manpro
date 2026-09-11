<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Manajemen Proyek' }}</title>
    @php($readOnly = session('guest_mode', false))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { overscroll-behavior-y: none; }
        body { overflow-x: hidden; font-family: 'Roboto', sans-serif; }
        .form-field-label { display: block; margin-bottom: 0.25rem; font-size: 0.75rem; font-weight: 600; color: #475569; }
        .read-only main form[method="POST"], .read-only main details { display: none; }
            .read-only main section:has(> form[action*="/progress/planned"]),
            .read-only main section:has(> form[action$="/progress"]),
            .read-only main section:has(> form[action*="/photos"]),
            .read-only main section:has(> form[action*="/cash-flows"]) { display: none; }
            .read-only main > div:has(> section > form[action*="/cash-flows"]) > section:nth-child(2) { grid-column: 1 / -1; }
            .read-only main > div:has(> section > form[action*="/photos"]) { grid-template-columns: 1fr; }
            .read-only main .progress-chart { grid-column: 1 / -1; }
            .read-only main .action-column { display: none; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 {{ $readOnly ? 'read-only' : '' }}">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ route('projects.index') }}" class="flex items-center gap-3" aria-label="PJ Ambaraloka">
                <img src="{{ config('app.logo_url') ?: asset('storage/logo-pt.jpeg') }}" alt="PJ Ambaraloka" class="h-10 w-auto object-contain">
                <!-- <span class="text-lg font-bold tracking-tight">PJ Ambaraloka</span> -->
            </a>
            <div class="flex items-center gap-4 text-sm text-slate-500">
                @auth @if (! session('guest_mode', false))
                    <a href="{{ route('procurements.index') }}" class="font-semibold text-orange-600 hover:text-orange-700">Pengadaan</a>
                @endif @endauth
                <span>{{ auth()->user()->name ?? 'Tamu' }}</span>
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-orange-600 hover:text-orange-700">Keluar</button></form>
                @else
                    <a href="{{ route('login') }}" class="font-semibold text-orange-600 hover:text-orange-700">Masuk</a>
                @endauth
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-6 py-10">
        @if (session('success'))
            <div class="mb-6 border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-6" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
        <div class="max-h-full w-full max-w-lg overflow-y-auto border border-slate-200 bg-white p-6 shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between gap-4"><h2 id="edit-modal-title" class="text-lg font-semibold">Edit</h2><button type="button" class="text-2xl text-slate-500 hover:text-slate-900" onclick="closeEditModal()" aria-label="Tutup">&times;</button></div>
            <div id="edit-modal-content" class="mt-5"></div>
        </div>
    </div>
    <script>
        const editModal = document.getElementById('edit-modal');
        const editModalTitle = document.getElementById('edit-modal-title');
        const editModalContent = document.getElementById('edit-modal-content');

        if (document.body.classList.contains('read-only')) {
            document.querySelectorAll('main details').forEach((detail) => {
                if (detail.querySelector(':scope > summary')?.textContent.toLowerCase().includes('edit')) detail.remove();
            });
        }

        document.querySelectorAll('main details').forEach((detail) => {
            const summary = detail.querySelector(':scope > summary');
            if (document.body.classList.contains('read-only') || !summary || !summary.textContent.toLowerCase().includes('edit')) return;
            const editElements = Array.from(detail.children).filter((child) => child !== summary);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'inline-flex items-center bg-white px-3 py-2 text-sm font-semibold text-orange-600 hover:bg-orange-50';
            button.textContent = summary.textContent.trim();
            button.addEventListener('click', () => openEditModal(button.textContent, editElements));
            detail.replaceWith(button);
        });

        document.querySelectorAll('main a[href$="/edit"]').forEach((link) => {
            if (document.body.classList.contains('read-only')) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = link.className;
            button.textContent = link.textContent.trim();
            button.addEventListener('click', async () => {
                editModalTitle.textContent = 'Edit proyek';
                editModalContent.innerHTML = '<p class="text-sm text-slate-500">Memuat form...</p>';
                showEditModal();
                const response = await fetch(link.href);
                const html = await response.text();
                const form = new DOMParser().parseFromString(html, 'text/html').querySelector('main form[method="POST"]');
                editModalContent.replaceChildren(form || document.createTextNode('Form edit tidak tersedia.'));
                addMissingFieldLabels(editModalContent);
            });
            link.replaceWith(button);
        });

        function openEditModal(title, elements) {
            editModalTitle.textContent = title;
            elements.forEach((element) => addMissingFieldLabels(element));
            editModalContent.replaceChildren(...elements);
            showEditModal();
        }

        function showEditModal() {
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
        }

        function closeEditModal() {
            editModal.classList.add('hidden');
            editModal.classList.remove('flex');
        }

        editModal.addEventListener('click', closeEditModal);

        const fieldLabels = {
            name: 'Nama', location: 'Lokasi', start_date: 'Tanggal mulai', end_date: 'Tanggal selesai', budget: 'Anggaran', status: 'Status',
            search: 'Pencarian', material_search: 'Cari material', month: 'Bulan', date: 'Tanggal', description: 'Keperluan', detailed_description: 'Deskripsi lengkap',
            result: 'Hasil pekerjaan', material_name: 'Nama material', brand: 'Merk', unit: 'Satuan', current_stock: 'Stok awal', in_qty: 'Jumlah masuk', out_qty: 'Jumlah keluar',
            progress_pct: 'Progress (%)', week_start: 'Minggu mulai', planned_progress_pct: 'Target (%)', supplier_name: 'Nama supplier', phone: 'Nomor telepon',
            address: 'Alamat supplier', project_id: 'Project', quantity: 'Kuantitas', price: 'Harga satuan', email: 'Email', password: 'Password'
        };

        function addMissingFieldLabels(root = document) {
            root.querySelectorAll('input, select, textarea').forEach((field) => {
                if (field.type === 'hidden' || field.hasAttribute('data-hide-field-label') || !field.name || field.closest('label') || field.previousElementSibling?.classList.contains('form-field-label')) return;
                const name = field.name.replace(/^.*\[([^\]]+)\]$/, '$1');
                const label = document.createElement('span');
                label.className = 'form-field-label';
                label.textContent = fieldLabels[name] || name.replaceAll('_', ' ');
                field.parentNode.insertBefore(label, field);
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => addMissingFieldLabels());
        else addMissingFieldLabels();
        const procurementItems = document.getElementById('procurement-items');
        if (procurementItems) new MutationObserver(() => addMissingFieldLabels(procurementItems)).observe(procurementItems, { childList: true, subtree: true });
    </script>
</body>
</html>
