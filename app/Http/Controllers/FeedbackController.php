<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        if (auth()->user()->is_admin) {
            return redirect()->route('admin.feedbacks.index');
        }

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

    public function adminShow(Feedback $feedback)
    {
        $feedback->load('user');

        return view('admin.feedbacks.show', compact('feedback'));
    }

    public function adminUpdate(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,resolved',
        ]);

        $feedback->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.feedbacks.show', $feedback)
            ->with('success', 'Feedback status updated successfully.');
    }

    public function store(Request $request)
    {
        if (auth()->user()->is_admin) {
            return redirect()->route('admin.feedbacks.index');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        auth()->user()->feedbacks()->create($validated);

        return redirect()->route('feedback.index')->with('success', 'Feedback submitted successfully.');
    }
}
