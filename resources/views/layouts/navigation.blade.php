<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<nav x-data="{
    open: false,
    darkMode: localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
}"
     @click.away="open = false"
     class="fixed w-full top-0 z-50 backdrop-blur-sm bg-white/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-slate-800">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand -->
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition">
                    <div class="w-9 h-9 rounded-lg gradient-accent flex items-center justify-center text-white font-bold text-lg">
                        A
                    </div>
                    <span class="hidden sm:block text-lg font-bold bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400 bg-clip-text text-transparent">
                        ArthaPredict
                    </span>
                </a>
            </div>

            <!-- Navigation Links (Desktop) -->
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('dashboard') }}"
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' => request()->routeIs('dashboard'),
                        'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' => !request()->routeIs('dashboard'),
                    ])>
                    Dashboard
                </a>

                <a href="#"
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                    ])>
                    Stocks
                </a>

                <a href="#"
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                    ])>
                    Watchlist
                </a>

                <a href="#"
                    @class([
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                    ])>
                    Analysis
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
                <!-- Theme Toggle -->
                <button
                    @click="darkMode = !darkMode; document.documentElement.classList.toggle('dark'); localStorage.setItem('darkMode', darkMode)"
                    class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    <svg class="w-5 h-5 block dark:hidden text-slate-700" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg class="w-5 h-5 hidden dark:block text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.293 2.293a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zm2.828 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm0 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-2.828 2.828a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zm2.828-4.828a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm0-4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1zM5.707 5.707a1 1 0 010 1.414L5 7.828a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zm0 9.586a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM3.707 3.707a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414L3.707 5.12a1 1 0 010-1.414zm0 9.586a1 1 0 011.414 0l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 010-1.414zM10 18a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <!-- User Dropdown -->
                <div x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false" class="relative hidden sm:block">
                    <button
                        @click="dropdownOpen = !dropdownOpen"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <div class="w-8 h-8 rounded-full gradient-accent flex items-center justify-center text-white text-xs font-bold">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                        <span class="hidden sm:inline max-w-[120px] truncate">{{ Auth::user()->name ?? 'User' }}</span>
                        <svg class="w-4 h-4" :class="{ 'rotate-180': dropdownOpen }" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-show="dropdownOpen"
                        class="absolute right-0 mt-2 w-48 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-lg py-1 z-50">

                        <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700">
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-50">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-xs text-slate-600 dark:text-slate-400">{{ Auth::user()->email ?? '' }}</p>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Profile
                        </a>

                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Settings
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="block">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border-t border-slate-200 dark:border-slate-700 transition">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    @click="open = !open"
                    class="md:hidden p-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="open" class="md:hidden border-t border-slate-200 dark:border-slate-800">
            <div class="px-4 py-3 space-y-2">
                <a href="{{ route('dashboard') }}"
                   class="block  px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Dashboard
                </a>
                <a href="#"
                   class="block px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Stocks
                </a>
                <a href="#"
                   class="block px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Watchlist
                </a>
                <a href="#"
                   class="block px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Analysis
                </a>
            </div>

            <!-- Mobile User Info -->
            <div class="border-t border-slate-200 dark:border-slate-800 px-4 py-3">
                <p class="font-medium text-slate-900 dark:text-slate-50 text-sm">{{ Auth::user()->name ?? 'User' }}</p>
                <p class="text-xs text-slate-600 dark:text-slate-400">{{ Auth::user()->email ?? '' }}</p>

                <div class="mt-3 space-y-2">
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Padding to account for fixed nav -->
<div class="h-16"></div>
