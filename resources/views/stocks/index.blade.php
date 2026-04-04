@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
    <div>
        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-bold">Stocks List</h3>
        <p class="mt-1 text-gray-500 dark:text-gray-400">Browse all available stocks and technical analytics.</p>
    </div>
    
    <div class="mt-4 md:mt-0 relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none">
                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </span>
        <input type="text" class="w-full md:w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-200 transition-colors" placeholder="Search by symbol...">
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap">
            <thead>
                <tr class="text-left bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-300 font-semibold">
                    <th class="px-6 py-4">Symbol</th>
                    <th class="px-6 py-4">Company Name</th>
                    <th class="px-6 py-4 text-right">Last Price</th>
                    <th class="px-6 py-4">Sector</th>
                    <th class="px-6 py-4">Exchange</th>
                    <th class="px-6 py-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($stocks as $stock)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800 dark:text-white">{{ $stock->symbol }}</div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                        {{ $stock->name }}
                    </td>
                    <td class="px-6 py-4 text-right font-medium text-gray-800 dark:text-gray-200">
                        @php
                            $latest = $stock->prices()->latest('date')->first();
                        @endphp
                        {{ $latest ? '$' . number_format($latest->close, 2) : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                        {{ $stock->sector ?? 'Unknown' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-md bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                            {{ $stock->exchange ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('stocks.show', $stock->symbol) }}" class="inline-flex items-center justify-center px-4 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 rounded-lg text-sm font-medium transition">
                            View Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p>No stocks available at the moment.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
        {{ $stocks->links() }}
    </div>
</div>
@endsection
