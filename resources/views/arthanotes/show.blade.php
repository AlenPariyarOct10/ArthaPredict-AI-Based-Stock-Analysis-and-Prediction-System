@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 px-4 py-3 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-300 bg-red-50 text-red-700 px-4 py-3 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('arthanotes.index') }}" class="text-sm rounded-md border border-border px-3 py-1.5 hover:bg-muted transition">
            Back to Feed
        </a>
    </div>

    <article class="bg-card text-card-foreground shadow-sm rounded-xl border border-border overflow-hidden">
        <div class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">{{ $note->user->name }} <span class="text-xs text-muted-foreground">({{ $note->user->is_admin ? 'Admin' : 'User' }})</span></p>
                    <p class="text-xs text-muted-foreground">{{ $note->created_at->format('M d, Y h:i A') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($note->is_pinned)
                        <span class="text-[11px] rounded-full bg-amber-100 text-amber-700 px-2 py-1">Pinned</span>
                    @endif
                    <span class="text-[11px] rounded-full bg-blue-100 text-blue-700 px-2 py-1">{{ str_replace('_', ' ', $note->type) }}</span>
                </div>
            </div>

            <h1 class="mt-3 text-2xl font-bold">{{ $note->title }}</h1>
            <div class="mt-3 prose prose-sm max-w-none dark:prose-invert">{!! $note->content !!}</div>

            @if($note->hashtags)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($note->hashtags as $tag)
                        <a href="{{ route('arthanotes.index', ['hashtag' => $tag]) }}" class="text-xs rounded-full bg-muted px-2 py-1 hover:bg-muted/70 transition">#{{ $tag }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        @if($note->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($note->image_path) }}" alt="ArthaNote image" class="w-full max-h-96 object-cover border-t border-border">
        @endif

        <div class="p-5 border-t border-border">
            @php
                $isLikedByUser = $note->likes->contains('user_id', auth()->id());
            @endphp
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('arthanotes.like.toggle', $note) }}" class="arthanote-like-form" data-liked="{{ $isLikedByUser ? '1' : '0' }}">
                    @csrf
                    <button type="submit" class="arthanote-like-btn rounded-lg border border-border px-3 py-1.5 text-sm hover:bg-muted transition {{ $isLikedByUser ? 'bg-pink-50 border-pink-200 text-pink-600' : '' }}">
                        Arthapurna ❤️ (<span class="arthanote-like-count">{{ $note->likes_count }}</span>)
                    </button>
                </form>
                <span class="text-sm text-muted-foreground">{{ $note->all_comments_count }} comments</span>
            </div>

            <div class="mt-5">
                <h3 class="text-sm font-semibold mb-2">Add a Comment</h3>
                <form method="POST" action="{{ route('arthanotes.comments.store', $note) }}" class="space-y-2">
                    @csrf
                    <textarea name="body" rows="3" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="Write your thoughts..." required></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="text-xs rounded-md gradient-accent text-white px-3 py-1.5">Comment</button>
                    </div>
                </form>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($note->comments as $comment)
                    @include('arthanotes._comment', ['comment' => $comment, 'note' => $note, 'depth' => 0])
                @empty
                    <p class="text-sm text-muted-foreground">No comments yet. Start the discussion.</p>
                @endforelse
            </div>
        </div>
    </article>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        document.querySelectorAll('.arthanote-like-form').forEach((form) => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const button = form.querySelector('.arthanote-like-btn');
                const countEl = form.querySelector('.arthanote-like-count');
                if (!button || !countEl) return;

                button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Like request failed');
                    }

                    const data = await response.json();
                    countEl.textContent = data.likes_count;

                    if (data.liked) {
                        button.classList.add('bg-pink-50', 'border-pink-200', 'text-pink-600');
                    } else {
                        button.classList.remove('bg-pink-50', 'border-pink-200', 'text-pink-600');
                    }
                } catch (error) {
                    console.error(error);
                    form.submit();
                } finally {
                    button.disabled = false;
                }
            });
        });
    });
</script>
@endpush
