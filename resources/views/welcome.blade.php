<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2d3748">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="description"
        content="ArthaPredict: Advanced AI-driven stock analysis and prediction for NEPSE and global markets using Machine Learning algorithms.">
    <link rel="shortcut icon" href="{{ asset('assets/images/Logo.png') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|space-mono:400,700" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /*! tailwindcss v4.0.7 | MIT License */
            @import 'tailwindcss';
        </style>
    @endif

    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        @keyframes pulse-subtle {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        @keyframes slide-up {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-pulse-subtle {
            animation: pulse-subtle 3s ease-in-out infinite;
        }

        .animate-slide-up {
            animation: slide-up 0.6s ease-out;
        }

        .gradient-accent {
            background: #0078d7;
        }

        .dark .gradient-accent {
            background: #0078d7;
        }

    </style>
</head>

<body
    class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-50 font-sans antialiased">
    <!-- Navigation -->
    <nav
        class="fixed w-full top-0 z-50 backdrop-blur-md bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 ">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        <img class="w-8 h-8" src="{{ asset('assets/images/Logo.png') }}" alt="" srcset="">
                    </div>

                    <span
                        class="text-lg font-bold text-primary dark:text-primary-light">
                        ArthaPredict
                    </span>
                </div>

                <div class="hidden md:flex items-center gap-8">
                    <a href="#features"
                        class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Features</a>
                    <a href="#algorithms"
                        class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Algorithms</a>
                    <a href="#how-it-works"
                        class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">How
                        It Works</a>
                </div>

                <div class="flex items-center gap-4">
                    <button id="theme-toggle"
                        class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        <i class="fa-solid fa-moon w-5 h-5 block dark:hidden text-slate-700"></i>
                        <i class="fa-solid fa-sun w-5 h-5 hidden dark:block text-slate-300"></i>
                    </button>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="px-6 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-medium">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                    class="px-6 py-2 rounded-lg gradient-accent text-white hover:opacity-90 transition font-medium">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 ">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div
                        class="inline-block px-4 py-2 rounded-full bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
                        <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">AI-Powered Stock
                            Analysis</span>
                    </div>
                    <h1 class="text-5xl md:text-6xl font-bold leading-tight">
                        <span class="block">Predict Stock</span>
                        <span class="text-primary dark:text-primary-light">Trends with AI</span>
                    </h1>
                    <p class="text-xl text-slate-600 dark:text-slate-400 leading-relaxed">
                        ArthaPredict combines advanced machine learning algorithms with real-time stock market data to
                        provide intelligent predictions for better investment decisions.
                    </p>
                    <div class="flex flex-wrap gap-4 pt-4">
                        <button
                            class="px-8 py-3 rounded-lg gradient-accent text-white hover:opacity-90 transition font-semibold shadow-lg hover:shadow-xl">
                            Start Predicting Now
                        </button>
                        <button
                            class="px-8 py-3 rounded-lg border-2 border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition font-semibold">
                            Learn More
                        </button>
                    </div>
                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 border-t border-slate-200 dark:border-slate-700">
                        <div
                            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/70 px-4 py-4">
                            <p class="text-2xl font-bold">{{ number_format($landingStats['stockCount']) }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Active Stocks</p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/70 px-4 py-4">
                            <p class="text-2xl font-bold">{{ number_format($landingStats['latestPriceCount']) }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Latest Price Records</p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/70 px-4 py-4">
                            <p class="text-2xl font-bold">{{ number_format($landingStats['predictionCount']) }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Saved Predictions</p>
                        </div>
                    </div>
                </div>

                <div class="relative h-[500px] hidden md:block">
                    <!-- Chart Container -->
                    <div
                        class="absolute inset-0 bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-8 border border-blue-200 dark:border-blue-800 overflow-hidden">
                        <div class="absolute inset-0 opacity-10">
                            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                <defs>
                                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor"
                                            stroke-width="0.5" />
                                    </pattern>
                                </defs>
                                <rect width="100" height="100" fill="url(#grid)" />
                            </svg>
                        </div>

                        <!-- Animated Chart Bars -->
                        <div class="relative h-full flex items-end justify-around gap-3 pb-4">
                            <div class="flex-1 bg-primary opacity-80 animate-slide-up"
                                style="height: 40%;"></div>
                            <div class="flex-1 bg-primary-light opacity-80 animate-slide-up"
                                style="height: 65%; animation-delay: 0.1s;"></div>
                            <div class="flex-1 bg-primary opacity-80 animate-slide-up"
                                style="height: 45%; animation-delay: 0.2s;"></div>
                            <div class="flex-1 bg-primary-light opacity-80 animate-slide-up"
                                style="height: 75%; animation-delay: 0.3s;"></div>
                            <div class="flex-1 bg-primary opacity-80 animate-slide-up"
                                style="height: 55%; animation-delay: 0.4s;"></div>
                        </div>

                        <!-- Floating Elements -->
                        <div
                            class="absolute top-8 right-8 w-12 h-12 bg-primary opacity-80 animate-float">
                        </div>
                        <div class="absolute bottom-16 left-8 w-10 h-10 bg-primary-light opacity-70 animate-float"
                            style="animation-delay: 1s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Market Snapshot -->
    <section class="pb-20 px-4 ">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold">Live Market Snapshot</h2>
                    <p class="text-slate-600 dark:text-slate-400 mt-2">
                        @if ($landingStats['latestTradingDate'])
                            Latest trading date:
                            {{ \Carbon\Carbon::parse($landingStats['latestTradingDate'])->format('M d, Y') }}
                        @else
                            No stock prices are available yet.
                        @endif
                    </p>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    Showing up to {{ is_countable($featuredStocks) ? count($featuredStocks) : 0 }} tracked stocks from the database
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                @forelse ($featuredStocks as $stock)
                    @php
                        $latestPrice = $stock->prices->first();
                        $nextPrediction = $stock->predictions->first();
                    @endphp
                    <div
                        class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-lg transition">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-2xl font-bold">{{ $stock->symbol }}</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $stock->name }}</p>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                {{ $stock->exchange ?? 'NEPSE' }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Latest Close</span>
                                <span class="font-semibold">
                                    {{ $latestPrice ? number_format((float) $latestPrice->close, 2) : 'N/A' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Latest Volume</span>
                                <span class="font-semibold">
                                    {{ $latestPrice && $latestPrice->volume ? number_format((float) $latestPrice->volume) : 'N/A' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Next Prediction</span>
                                <span class="font-semibold">
                                    {{ $nextPrediction ? number_format((float) $nextPrediction->predicted_price, 2) : 'Pending' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500 dark:text-slate-400">Prediction Date</span>
                                <span class="font-semibold">
                                    {{ $nextPrediction ? \Carbon\Carbon::parse($nextPrediction->target_date)->format('M d, Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>

                        @auth
                            <a href="{{ route('stocks.show', $stock->symbol) }}"
                                class="mt-6 inline-flex items-center text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                View stock details
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="mt-6 inline-flex items-center text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                Sign in to explore
                            </a>
                        @endauth
                    </div>
                @empty
                    <div
                        class="md:col-span-3 p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-center">
                        <h3 class="text-xl font-semibold mb-2">No Market Data Yet</h3>
                        <p class="text-slate-600 dark:text-slate-400">
                            Add stock records and price history to start showing live stats on the landing page.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4  bg-slate-50 dark:bg-slate-800/50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Powerful Features</h2>
                <p class="text-xl text-slate-600 dark:text-slate-400">Everything you need for intelligent stock analysis
                    and prediction</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Data Visualization</h3>
                    <p class="text-slate-600 dark:text-slate-400">Browse historical prices, closing trends, and future
                        forecasts backed by the live stock records stored in ArthaPredict.</p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">AI Predictions</h3>
                    <p class="text-slate-600 dark:text-slate-400">Prediction cards surface saved outputs from Moving
                        Average, XGBoost, and LSTM models so users can compare forecast styles quickly.</p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 5a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Watchlist Management</h3>
                    <p class="text-slate-600 dark:text-slate-400">Authenticated users can track selected stocks, revisit
                        forecast history, and focus on symbols that matter to them most.</p>
                </div>

                <!-- Feature 4 -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">NEPSE Integration</h3>
                    <p class="text-slate-600 dark:text-slate-400">Complete support for Nepal Stock Exchange with
                        localized data and insights for Nepali investors.</p>
                </div>

                <!-- Feature 5 -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">User-Friendly Interface</h3>
                    <p class="text-slate-600 dark:text-slate-400">Intuitive design accessible to both beginners and
                        experienced investors for easy navigation.</p>
                </div>

                <!-- Feature 6 -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Export & Analysis</h3>
                    <p class="text-slate-600 dark:text-slate-400">Export data in CSV, Excel, and PDF formats for further
                        analysis and detailed reporting.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Algorithms Section -->
    <section id="algorithms" class="py-20 px-4  bg-slate-50 dark:bg-slate-800/50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Advanced ML Algorithms</h2>
                <p class="text-xl text-slate-600 dark:text-slate-400">Three powerful algorithms powering ArthaPredict
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Moving Average -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Moving Average</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Statistical technique that smooths price
                        fluctuations over defined periods to reveal long-term trends.</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400"><span class="font-bold">Best for:</span>
                        Identifying trend direction and momentum</p>
                </div>

                <!-- Linear Regression -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Random Forest</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">The Random Forest algorithm is a versatile, supervised machine learning method that builds an ensemble of multiple decision trees to produce a single, more accurate and stable prediction.</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400"><span class="font-bold">Best for:</span> Basic
                        price forecasting and trend projection</p>
                </div>

                <!-- LSTM -->
                <div
                    class="p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition">
                    <div class="w-12 h-12 rounded-lg gradient-accent flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">LSTM Neural Network</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">Deep learning model that learns long-term
                        dependencies in time-series data for advanced pattern recognition.</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400"><span class="font-bold">Best for:</span>
                        Complex non-linear trend prediction</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 px-4 ">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">How ArthaPredict Works</h2>
                <p class="text-xl text-slate-600 dark:text-slate-400">Simple steps to start analyzing and predicting
                    stocks</p>
            </div>

            <div class="grid md:grid-cols-4 gap-8">
                <div class="relative">
                    <div class="flex flex-col items-center">
                        <div
                            class="w-16 h-16 rounded-full gradient-accent flex items-center justify-center text-white font-bold text-xl mb-4">
                            1</div>
                        <h3 class="font-bold text-lg mb-2 text-center">Select Stock</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-center text-sm">Choose from NEPSE or global
                            markets</p>
                    </div>
                    <div
                        class="hidden md:block absolute top-8 right-0 translate-x-1/2 w-12 h-0.5 bg-gradient-to-r from-blue-400 to-transparent">
                    </div>
                </div>

                <div class="relative">
                    <div class="flex flex-col items-center">
                        <div
                            class="w-16 h-16 rounded-full gradient-accent flex items-center justify-center text-white font-bold text-xl mb-4">
                            2</div>
                        <h3 class="font-bold text-lg mb-2 text-center">View Trends</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-center text-sm">Visualize historical patterns
                            and data</p>
                    </div>
                    <div
                        class="hidden md:block absolute top-8 right-0 translate-x-1/2 w-12 h-0.5 bg-gradient-to-r from-blue-400 to-transparent">
                    </div>
                </div>

                <div class="relative">
                    <div class="flex flex-col items-center">
                        <div
                            class="w-16 h-16 rounded-full gradient-accent flex items-center justify-center text-white font-bold text-xl mb-4">
                            3</div>
                        <h3 class="font-bold text-lg mb-2 text-center">Get Prediction</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-center text-sm">AI models forecast future
                            price movements</p>
                    </div>
                    <div
                        class="hidden md:block absolute top-8 right-0 translate-x-1/2 w-12 h-0.5 bg-gradient-to-r from-blue-400 to-transparent">
                    </div>
                </div>

                <div>
                    <div class="flex flex-col items-center">
                        <div
                            class="w-16 h-16 rounded-full gradient-accent flex items-center justify-center text-white font-bold text-xl mb-4">
                            4</div>
                        <h3 class="font-bold text-lg mb-2 text-center">Make Decision</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-center text-sm">Use insights for confident
                            investing</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="border-t border-gray-200 dark:border-gray-800 py-6 px-4 ">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-3 gap-8 mb-8">

                <div>
                    <h4 class="font-bold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Features</a>
                        </li>
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Pricing</a>
                        </li>
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Security</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Company</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">About</a>
                        </li>
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Blog</a>
                        </li>
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Contact</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Privacy</a>
                        </li>
                        <li><a href="#" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Terms</a>
                        </li>
                        <li><a href="#"
                                class="hover:text-purple-600 dark:hover:text-purple-400 transition">Disclaimer</a></li>
                    </ul>
                </div>
            </div>
            <div
                class="border-t border-gray-200 dark:border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-600 dark:text-gray-400 text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'ArthaPredict') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

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

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>

</html>