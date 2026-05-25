<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Login - {{ config('app.name', 'ArthaPredict') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-900 text-gray-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-700 flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    A
                </div>
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-gray-800 shadow-2xl overflow-hidden sm:rounded-2xl border border-gray-700">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold text-white">Admin Portal</h2>
                <p class="text-gray-400 mt-2">Please sign in to access administrative tools.</p>
            </div>

            <!-- Session Status -->
            @if(session('status'))
                <div class="mb-4 font-medium text-sm text-green-400">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block font-medium text-sm text-gray-300">Email Address</label>
                    <input id="email" class="block mt-1 w-full bg-gray-700 border-gray-600 text-white focus:border-amber-500 focus:ring-amber-500 rounded-lg shadow-sm" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                    @error('email')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mt-6">
                    <label for="password" class="block font-medium text-sm text-gray-300">Password</label>
                    <input id="password" class="block mt-1 w-full bg-gray-700 border-gray-600 text-white focus:border-amber-500 focus:ring-amber-500 rounded-lg shadow-sm" type="password" name="password" required autocomplete="current-password" />
                    @error('password')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="block mt-6">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded bg-gray-700 border-gray-600 text-amber-500 shadow-sm focus:ring-amber-500" name="remember">
                        <span class="ms-2 text-sm text-gray-400">Remember me</span>
                    </label>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition duration-150 ease-in-out">
                        Sign In as Admin
                    </button>
                </div>
                
                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-gray-200 transition">
                        Back to User Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
