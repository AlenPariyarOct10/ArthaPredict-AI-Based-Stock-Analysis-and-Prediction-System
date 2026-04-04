@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-bold">My Watchlist</h3>
    <p class="mt-1 text-gray-500 dark:text-gray-400">Keep track of your favorite stocks and their current trends.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($watchlists as $watchlist)
        @php
            $stock = $watchlist->stock;
            $latest = $stock->prices()->latest('date')->first();
            $previous = $stock->prices()->orderBy('date', 'desc')->skip(1)->first();
            
            $change = 0;
            $percent = 0;
            if ($latest && $previous) {
                $change = $latest->close - $previous->close;
                $percent = ($change / $previous->close) * 100;
            }
            $isPositive = $change >= 0;
        @endphp
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col transform hover:-translate-y-1 transition duration-300 relative group">
            
            <!-- Remove from watchlist button -->
            <form action="{{ route('watchlist.toggle') }}" method="POST" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                @csrf
                <input type="hidden" name="stock_id" value="{{ $stock->id }}">
                <button type="submit" class="text-gray-400 hover:text-red-500 focus:outline-none p-1 bg-gray-50 dark:bg-gray-700 rounded-full" title="Remove">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </form>

            <a href="{{ route('stocks.show', $stock->symbol) }}" class="flex-grow">
                <div class="flex items-center justify-between mt-2">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $stock->symbol }}</h4>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $isPositive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $isPositive ? '+' : '' }}{{ number_format($percent, 2) }}%
                    </span>
                </div>
                
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate mt-1">{{ $stock->name }}</p>
                
                <div class="mt-6 text-3xl font-extrabold text-gray-800 dark:text-white">
                    ${{ $latest ? number_format($latest->close, 2) : '---' }}
                </div>
                
                @if($latest)
                <div class="mt-4 flex items-center text-sm">
                    <span class="{{ $isPositive ? 'text-green-500' : 'text-red-500' }} flex items-center font-medium">
                        @if($isPositive)
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        @else
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                        @endif
                        ${{ number_format(abs($change), 2) }}
                    </span>
                    <span class="ml-2 text-gray-400">Today</span>
                </div>
                @endif
            </a>
        </div>
    @empty
        <div class="col-span-full py-16 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center text-center px-4">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
            <h4 class="text-xl font-bold text-gray-700 dark:text-gray-200">Your watchlist is empty</h4>
            <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-sm">You haven't added any stocks to your watchlist yet. Browse stocks and click the "Watchlist" button to add them here.</p>
            <a href="{{ route('stocks.index') }}" class="mt-6 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">Browse Stocks</a>
        </div>
    @endforelse
</div>
@endsection
