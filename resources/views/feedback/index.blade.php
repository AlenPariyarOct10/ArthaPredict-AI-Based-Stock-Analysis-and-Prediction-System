@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6">
        <h2 class="text-2xl font-bold">Submit Feedback</h2>
        <p class="text-sm text-muted-foreground mt-1">
            Share your experience and suggestions to help us improve ArthaPredict.
        </p>

        @if(session('success'))
            <div class="mt-4 rounded-lg border border-blue-300 bg-blue-50 text-blue-700 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 text-red-700 px-4 py-3 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('feedback.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="subject" class="block text-sm font-medium mb-2">Subject</label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="{{ old('subject') }}"
                    placeholder="Brief title of your feedback"
                    class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                >
            </div>
            <div>
                <label for="message" class="block text-sm font-medium mb-2">Message</label>
                <textarea
                    id="message"
                    name="message"
                    rows="5"
                    placeholder="Describe your issue, idea, or suggestion"
                    class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                >{{ old('message') }}</textarea>
            </div>
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg gradient-accent px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-90 transition"
                >
                    Submit Feedback
                </button>
            </div>
        </form>
    </div>

    <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-xl font-semibold">Your Submitted Feedback</h3>
            <span class="text-xs text-muted-foreground">Newest first</span>
        </div>

        @if($feedbacks->isEmpty())
            <div class="mt-6 rounded-lg border border-dashed border-border px-6 py-8 text-center text-muted-foreground">
                You have not submitted feedback yet.
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach($feedbacks as $feedback)
                    <div class="rounded-lg border border-border p-4 bg-background/60">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-semibold">{{ $feedback->subject }}</h4>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                                {{ $feedback->status === 'resolved' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                {{ $feedback->status === 'reviewed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                {{ $feedback->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}">
                                {{ ucfirst($feedback->status) }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">{{ $feedback->message }}</p>
                        <p class="mt-3 text-xs text-muted-foreground">
                            Submitted: {{ $feedback->created_at->format('M d, Y h:i A') }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $feedbacks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
