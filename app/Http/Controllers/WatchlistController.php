<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index()
    {
        $watchlists = auth()->user()->watchlists()->with('stock')->get();
        return view('watchlist.index', compact('watchlists'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id'
        ]);

        $user = auth()->user();
        $watchlist = Watchlist::where('user_id', $user->id)
                            ->where('stock_id', $request->stock_id)
                            ->first();

        if ($watchlist) {
            $watchlist->delete();
            return response()->json(['status' => 'removed', 'message' => 'Removed from watchlist']);
        } else {
            Watchlist::create([
                'user_id' => $user->id,
                'stock_id' => $request->stock_id
            ]);
            return response()->json(['status' => 'added', 'message' => 'Added to watchlist']);
        }
    }
}
