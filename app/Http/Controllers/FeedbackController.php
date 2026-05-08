<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedbacks = auth()->user()
            ->feedbacks()
            ->latest()
            ->paginate(10);

        return view('feedback.index', compact('feedbacks'));
    }

    public function adminIndex()
    {
        $feedbacks = Feedback::with('user')
            ->latest()
            ->paginate(12);

        return view('admin.feedbacks.index', compact('feedbacks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        auth()->user()->feedbacks()->create($validated);

        return redirect()->route('feedback.index')->with('success', 'Feedback submitted successfully.');
    }
}
