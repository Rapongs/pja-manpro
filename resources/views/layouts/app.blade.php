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
                <img src="{{ asset('storage/logo-pt.jpeg') }}" alt="PJ Ambaraloka" class="h-10 w-auto object-contain">
                <!-- <span class="text-lg font-bold tracking-tight">PJ Ambaraloka</span> -->
            </a>
            <div class="flex items-center gap-4 text-sm text-slate-500">
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
</body>
</html>
