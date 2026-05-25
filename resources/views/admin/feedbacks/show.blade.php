@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 px-4 py-3 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-300 bg-red-50 text-red-700 px-4 py-3 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold">Feedback Detail</h2>
            <p class="text-sm text-muted-foreground mt-1">
                Full client feedback record and submission details.
            </p>
        </div>
        <a href="{{ route('admin.feedbacks.index') }}"
           class="inline-flex items-center rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition">
            Back to Feedbacks
        </a>
    </div>

    <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-xs uppercase tracking-wider text-muted-foreground">Client</p>
                <p class="mt-1 font-semibold">{{ $feedback->user->name ?? 'Unknown User' }}</p>
                <p class="text-sm text-muted-foreground">{{ $feedback->user->email ?? 'No email' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-muted-foreground">Status</p>
                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                    {{ $feedback->status === 'resolved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                    {{ $feedback->status === 'reviewed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                    {{ $feedback->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}">
                    {{ ucfirst($feedback->status) }}
                </span>
            </div>
        </div>

        <div class="mt-6 border-t border-border pt-6">
            <p class="text-xs uppercase tracking-wider text-muted-foreground">Subject</p>
            <h3 class="mt-1 text-lg font-semibold">{{ $feedback->subject }}</h3>
        </div>

        <div class="mt-6 border-t border-border pt-6">
            <p class="text-xs uppercase tracking-wider text-muted-foreground">Message</p>
            <div class="mt-2 rounded-lg border border-border bg-background/60 p-4 whitespace-pre-wrap leading-relaxed">
                {{ $feedback->message }}
            </div>
        </div>

        <div class="mt-6 border-t border-border pt-6">
            <p class="text-xs uppercase tracking-wider text-muted-foreground">Update Status</p>
            <form method="POST" action="{{ route('admin.feedbacks.update', $feedback) }}" class="mt-3 flex flex-col sm:flex-row gap-3 sm:items-end">
                @csrf
                @method('PATCH')

                <div>
                    <label for="status" class="block text-sm font-medium mb-2">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="w-full sm:w-52 rounded-lg border border-border bg-background px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        required
                    >
                        <option value="pending" {{ $feedback->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="reviewed" {{ $feedback->status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="resolved" {{ $feedback->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg gradient-accent px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-90 transition"
                >
                    Save Status
                </button>
            </form>
        </div>

        @if($feedback->reply)
            <div class="mt-6 border-t border-border pt-6">
                <p class="text-xs uppercase tracking-wider text-muted-foreground">Admin Reply</p>
                <div class="mt-2 rounded-lg border border-border bg-background/60 p-4 whitespace-pre-wrap leading-relaxed">
                    {{ $feedback->reply }}
                </div>
            </div>
        @endif

        <div class="mt-6 border-t border-border pt-6 text-sm text-muted-foreground">
            Submitted on {{ $feedback->created_at->format('M d, Y h:i A') }}
        </div>
    </div>
</div>
@endsection
