<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2d3748">
    <title>ArthaPredict - Login</title>
    <meta name="description" content="Login to ArthaPredict - AI-Based Stock Analysis & Prediction System">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-pulse-subtle { animation: pulse-subtle 3s ease-in-out infinite; }
        .gradient-accent { background: linear-gradient(135deg, #2d5a3a 0%, #1a3a2a 100%); }
        .dark .gradient-accent { background: linear-gradient(135deg, #4a7c5c 0%, #2d5a3a 100%); }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950 text-slate-900 dark:text-slate-50 font-sans antialiased">
<!-- Top Navigation -->
<nav class="fixed w-full top-0 z-40 backdrop-blur-md bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg gradient-accent flex items-center justify-center text-white font-bold text-lg">
                    A
                </div>
                <span class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400 bg-clip-text text-transparent">
                            ArthaPredict
                        </span>
            </a>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.293 2.293a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zm2.828 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm0 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-2.828 2.828a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zm2.828-4.828a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm0-4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1zM5.707 5.707a1 1 0 010 1.414L5 7.828a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zm0 9.586a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM3.707 3.707a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414L3.707 5.12a1 1 0 010-1.414zm0 9.586a1 1 0 011.414 0l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 010-1.414zM10 18a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="min-h-screen pt-24 pb-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Welcome Message -->
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold mb-2">Welcome Back</h1>
            <p class="text-slate-600 dark:text-slate-400">Sign in to your ArthaPredict account to access stock analysis and predictions</p>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 shadow-lg hover:shadow-xl transition">
            <!-- Session Status -->
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Login Failed</p>
                    <ul class="text-sm text-red-600 dark:text-red-300 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Email Address
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-0 dark:focus:ring-emerald-400 transition"
                        placeholder="you@example.com"
                    >
                    @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-0 dark:focus:ring-emerald-400 transition"
                        placeholder="••••••••"
                    >
                    @error('password')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-500 focus:ring-emerald-500 dark:focus:ring-emerald-400 cursor-pointer"
                    >
                    <label for="remember_me" class="ms-3 text-sm text-slate-600 dark:text-slate-400 cursor-pointer">
                        Remember me
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full mt-6 px-6 py-3 rounded-lg gradient-accent text-white font-semibold hover:opacity-90 transition shadow-md hover:shadow-lg"
                >
                    Sign In
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-300 dark:border-slate-600"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400">Don&apos;t have an account?</span>
                </div>
            </div>

            <!-- Sign Up Link -->
            <a
                href="{{ route('register') }}"
                class="w-full block text-center px-6 py-3 rounded-lg border-2 border-emerald-600 dark:border-emerald-400 text-emerald-600 dark:text-emerald-400 font-semibold hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition"
            >
                Create New Account
            </a>

            <!-- Forgot Password -->
            @if (Route::has('password.request'))
                <div class="mt-4 text-center">
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition font-medium"
                    >
                        Forgot your password?
                    </a>
                </div>
            @endif
        </div>

        <!-- Benefits Section -->
        <div class="mt-12 grid grid-cols-3 gap-4">
            <div class="text-center">
                <div class="flex justify-center mb-2">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">AI-Powered</p>
            </div>
            <div class="text-center">
                <div class="flex justify-center mb-2">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">Real-Time Data</p>
            </div>
            <div class="text-center">
                <div class="flex justify-center mb-2">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">Secure & Safe</p>
            </div>
        </div>
    </div>
</div>

<!-- Floating Background Elements -->
<div class="fixed top-20 right-10 w-32 h-32 bg-emerald-200 dark:bg-emerald-900/20 rounded-full opacity-20 animate-float pointer-events-none"></div>
<div class="fixed bottom-20 left-10 w-40 h-40 bg-teal-200 dark:bg-teal-900/20 rounded-full opacity-20 animate-float" style="animation-delay: 2s;"></div>

<!-- Theme Toggle Script -->
<script>
    function getStoredThemePreference() {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || theme === 'light') {
            return theme === 'dark';
        }

        const legacyValue = localStorage.getItem('darkMode');
        if (legacyValue === 'true' || legacyValue === 'false') {
            return legacyValue === 'true';
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applyThemePreference(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    }

    const themeToggle = document.getElementById('theme-toggle');
    applyThemePreference(getStoredThemePreference());

    themeToggle.addEventListener('click', () => {
        applyThemePreference(!document.documentElement.classList.contains('dark'));
    });
</script>
</body>
</html>
