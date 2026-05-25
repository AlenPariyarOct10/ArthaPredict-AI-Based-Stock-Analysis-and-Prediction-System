@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-card text-card-foreground shadow-sm rounded-xl border border-border p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold">Client Feedbacks</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Review feedback submitted by clients across the platform.
                </p>
            </div>
            <span class="text-xs text-muted-foreground">
                Showing {{ $feedbacks->firstItem() ?? 0 }}-{{ $feedbacks->lastItem() ?? 0 }} of {{ $feedbacks->total() }}
            </span>
        </div>

        @if($feedbacks->isEmpty())
            <div class="mt-6 rounded-lg border border-dashed border-border px-6 py-8 text-center text-muted-foreground">
                No client feedback has been submitted yet.
            </div>
        @else
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Submitted At</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($feedbacks as $feedback)
                            <tr class="hover:bg-muted/30 transition">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-medium">{{ $feedback->user->name ?? 'Unknown User' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $feedback->user->email ?? 'No email' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top font-medium">{{ $feedback->subject }}</td>
                                <td class="px-4 py-4 align-top text-sm text-muted-foreground max-w-md">
                                    {{ \Illuminate\Support\Str::limit($feedback->message, 150) }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                                        {{ $feedback->status === 'resolved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                        {{ $feedback->status === 'reviewed' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                        {{ $feedback->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}">
                                        {{ ucfirst($feedback->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-muted-foreground whitespace-nowrap">
                                    {{ $feedback->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <a href="{{ route('admin.feedbacks.show', $feedback) }}"
                                       class="inline-flex items-center rounded-md border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted transition">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $feedbacks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
