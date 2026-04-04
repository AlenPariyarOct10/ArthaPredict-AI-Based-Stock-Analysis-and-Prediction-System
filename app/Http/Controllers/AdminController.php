<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Feedback;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $usersCount = User::count();
        $stocksCount = Stock::count();
        $pendingFeedbackCount = Feedback::where('status', 'pending')->count();
        
        return view('admin.dashboard', compact('usersCount', 'stocksCount', 'pendingFeedbackCount'));
    }
    
    // Other admin methods (manage stocks, users, feedbacks) go here
}
