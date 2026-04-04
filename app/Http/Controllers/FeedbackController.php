<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = auth()->user()->feedbacks()->orderBy('created_at', 'desc')->get();
        return view('feedback.index', compact('feedbacks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        auth()->user()->feedbacks()->create($validated);

        return redirect()->back()->with('success', 'Feedback submitted successfully.');
    }
}
