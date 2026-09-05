<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | PJ Ambaraloka</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-6 text-slate-900" style="font-family: 'Roboto', sans-serif;">
    <main class="w-full max-w-md border border-slate-200 bg-white p-8 shadow-sm">
        <img src="{{ asset('storage/logo-pt.jpeg') }}" alt="PJ Ambaraloka" class="mx-auto h-20 w-auto object-contain">
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Masuk ke workspace</h1>
        @if ($errors->any())
            <div class="mt-6 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-4">
            @csrf
            <label class="block text-sm font-medium">Email<input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full border border-slate-300 px-3 py-2"></label>
            <label class="block text-sm font-medium">Password
                <span class="relative mt-1 block">
                    <input id="password" type="password" name="password" required class="w-full border border-slate-300 px-3 py-2 pr-24">
                    <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-orange-600 hover:text-orange-700" aria-controls="password" aria-label="Tampilkan password">Tampilkan</button>
                </span>
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button class="w-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-orange-600">Masuk</button>
        </form>
        <div class="my-6 flex items-center gap-3 text-xs uppercase tracking-widest text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>atau<span class="h-px flex-1 bg-slate-200"></span></div>
        <form method="POST" action="{{ route('guest.login') }}">
            @csrf
            <button class="w-full border border-orange-500 px-4 py-3 text-sm font-semibold text-orange-700 hover:bg-orange-50">Guest Login</button>
        </form>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');

        togglePassword.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            togglePassword.textContent = isVisible ? 'Tampilkan' : 'Sembunyikan';
            togglePassword.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    </script>
</body>
</html>
