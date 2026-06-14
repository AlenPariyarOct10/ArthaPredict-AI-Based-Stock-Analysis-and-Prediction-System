@extends('layouts.app')

@section('content')
    <div class="mb-8">
        <h3 class="text-gray-700 dark:text-gray-200 text-3xl font-bold">Predictive Analysis</h3>
        <p class="mt-1 text-gray-500 dark:text-gray-400">Deep dive into AI-driven stock price forecasts and model
            performance.</p>
    </div>

    <form method="GET" action="{{ route('analysis.index') }}"
          class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4 flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Eligibility</label>
            <select name="eligibility" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-2">
                <option value="all" @selected($eligibility === 'all')>All stocks</option>
                <option value="eligible" @selected($eligibility === 'eligible')>Eligible ({{ $minimumDatapoints }}+ usable datapoints)</option>
                <option value="ineligible" @selected($eligibility === 'ineligible')>Not eligible (<{{ $minimumDatapoints }} usable datapoints)</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Sort</label>
            <select name="sort" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-2">
                <option value="symbol_asc" @selected($sort === 'symbol_asc')>Symbol A-Z</option>
                <option value="datapoints_desc" @selected($sort === 'datapoints_desc')>Most datapoints first</option>
                <option value="datapoints_asc" @selected($sort === 'datapoints_asc')>Fewest datapoints first</option>
            </select>
        </div>
        <button class="px-5 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">Apply Filters</button>
    </form>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Predictions</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white">{{ $totalPredictions }}</h4>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active Models</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white">4 (LSTM, XGB, RF, MA)</h4>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-2.5 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Forecast Horizon</p>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white">Up to 30 Days</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Table -->
    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-white">Stock Forecast Comparison</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr
                        class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-semibold">Stock</th>
                        <th class="px-6 py-4 font-semibold">Usable Datapoints</th>
                        <th class="px-6 py-4 font-semibold">LSTM Prediction (1M)</th>
                        <th class="px-6 py-4 font-semibold">XGBoost Prediction (1M)</th>
                        <th class="px-6 py-4 font-semibold">Random Forest (1M)</th>
                        <th class="px-6 py-4 font-semibold">Moving Average Benchmark (1M)</th>
                        <th class="px-6 py-4 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($stocks as $stock)
                        @php
                            $lstmPred = $stock->predictions->where('model_type', 'lstm')->last();
                            $xgbPred = $stock->predictions->where('model_type', 'xgboost')->last();
                            $rfPred = $stock->predictions->where('model_type', 'random_forest')->last();
                            $maPred = $stock->predictions->where('model_type', 'moving_average')->last();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold mr-3">
                                        {{ substr($stock->symbol, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800 dark:text-gray-200">{{ $stock->symbol }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-700 dark:text-gray-200">{{ number_format($stock->datapoints_count) }}</div>
                                @if($stock->datapoints_count >= $minimumDatapoints)
                                    <span class="text-xs text-green-600 dark:text-green-400">Eligible</span>
                                @else
                                    <span class="text-xs text-red-600 dark:text-red-400">Not eligible</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($lstmPred)
                                    <div class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                        Rs. {{ number_format($lstmPred->predicted_price, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ $lstmPred->target_date }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">No Data</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($xgbPred)
                                    <div class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                        Rs. {{ number_format($xgbPred->predicted_price, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ $xgbPred->target_date }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">No Data</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($rfPred)
                                    <div class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                        Rs. {{ number_format($rfPred->predicted_price, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ $rfPred->target_date }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">No Data</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($maPred)
                                    <div class="text-sm font-bold text-amber-600 dark:text-amber-400">
                                        Rs. {{ number_format($maPred->predicted_price, 2) }}</div>
                                    <div class="text-xs text-gray-500">{{ $maPred->target_date }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">No Data</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('stocks.show', $stock->symbol) }}"
                                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    Details
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                        </path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($stocks->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ $stocks->firstItem() }} to {{ $stocks->lastItem() }}
                        of {{ $stocks->total() }} stocks
                    </p>
                    {{ $stocks->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Model Methodology</h4>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div
                        class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold mr-3 mt-1">
                        1</div>
                    <div>
                        <h5 class="font-bold text-gray-700 dark:text-gray-200">LSTM (Scratch Implementation)</h5>
                        <p class="text-sm text-gray-500 dark:text-gray-400">A Recurrent Neural Network architecture designed
                            to capture long-term dependencies in time-series data using Forget, Input, and Output gates.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div
                        class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold mr-3 mt-1">
                        2</div>
                    <div>
                        <h5 class="font-bold text-gray-700 dark:text-gray-200">XGBoost-Style Gradient Boosting (Scratch)</h5>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Handwritten regularized regression trees trained sequentially on residual errors.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs font-bold mr-3 mt-1">4</div>
                    <div>
                        <h5 class="font-bold text-gray-700 dark:text-gray-200">Moving Average Benchmark</h5>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Uses one validation-selected window across all stocks as an interpretable benchmark.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div
                        class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold mr-3 mt-1">
                        3</div>
                    <div>
                        <h5 class="font-bold text-gray-700 dark:text-gray-200">Random Forest</h5>
                        <p class="text-sm text-gray-500 dark:text-gray-400">An ensemble learning method that constructs multiple decision trees during training and outputs the mean prediction for improved accuracy and reduced overfitting.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Recent Training Activity</h4>
            <div class="space-y-4">
                @forelse($latestPredictions as $lp)
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-300 font-medium">{{ $lp->model_type }}</span>
                            <span class="text-gray-400 mx-2">for</span>
                            <span class="text-gray-800 dark:text-gray-100 font-bold">{{ $lp->stock->symbol }}</span>
                        </div>
                        <div class="text-gray-500">{{ $lp->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic text-center py-4">No recent training activity.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
