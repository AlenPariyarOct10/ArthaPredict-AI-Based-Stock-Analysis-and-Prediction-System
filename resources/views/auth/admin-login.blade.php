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
    <link rel="shortcut icon" href="{{ asset('assets/images/Logo.png') }}" type="image/x-icon">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-gray-900">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/">
                <div
                    class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-3xl shadow-md">
                    <img class="w-20 h-20" src="http://127.0.0.1:8000/assets/images/Logo.png" alt="" srcset="">
                </div>
            </a>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-8 py-10 bg-white shadow-lg overflow-hidden sm:rounded-lg border border-gray-200">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900">Admin Portal</h2>
                <p class="text-gray-600 mt-2">Please sign in to access administrative tools.</p>
            </div>

            <!-- Session Status -->
            @if(session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-blue-50 p-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block font-medium text-sm text-gray-700">Email Address</label>
                    <input id="email"
                        class="block mt-1 w-full border border-gray-300 text-gray-900 placeholder-gray-400 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm px-3 py-2"
                        type="email" name="email" value="{{ old('email') }}" required autofocus
                        autocomplete="username" />
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mt-6">
                    <label for="password" class="block font-medium text-sm text-gray-700">Password</label>
                    <input id="password"
                        class="block mt-1 w-full border border-gray-300 text-gray-900 placeholder-gray-400 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm px-3 py-2"
                        type="password" name="password" required autocomplete="current-password" />
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="block mt-6">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500"
                            name="remember">
                        <span class="ms-2 text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <div class="mt-8">
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        Sign In as Admin
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 transition">
                        Back to User Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>