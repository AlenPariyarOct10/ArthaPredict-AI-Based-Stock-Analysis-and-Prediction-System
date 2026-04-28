@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stat Cards -->
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Total Users</h3>
            <p class="text-3xl font-bold mt-2">{{ $usersCount }}</p>
        </div>
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Monitored Stocks</h3>
            <p class="text-3xl font-bold mt-2">{{ $stocksCount }}</p>
        </div>
        <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6 flex flex-col justify-between">
            <h3 class="text-lg font-medium text-muted-foreground">Pending Feedback</h3>
            <p class="text-3xl font-bold mt-2">{{ $pendingFeedbackCount }}</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded relative" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Model Training Section -->
    <div class="bg-card shadow-sm rounded-xl border border-border p-6 mt-8">
        <h2 class="text-xl font-bold mb-4">Train ML Model</h2>
        <p class="text-muted-foreground mb-6">
            Force re-train the predictive models for a specific stock. This will execute the machine learning pipeline and update the <strong>stock_predictions</strong> table with the freshest output.
        </p>

        <form action="{{ route('admin.train') }}" method="POST" class="space-y-4 max-w-md">
            @csrf
            <div>
                <label for="stock_id" class="block text-sm font-medium text-foreground mb-2">Select Stock Symbol</label>
                <select id="stock_id" name="stock_id" required
                        class="w-full bg-background border border-border text-foreground text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5 dark:border-border dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary dark:focus:border-primary">
                    <option value="" disabled selected>Choose a stock to train...</option>
                    @foreach($stocks as $stock)
                        <option value="{{ $stock->id }}">{{ $stock->symbol }} - {{ $stock->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" 
                    class="w-full text-white gradient-accent hover:opacity-90 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:focus:ring-emerald-800 transition shadow-sm">
                Initiate Training
            </button>
        </form>
    </div>
</div>
@endsection
