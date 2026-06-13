@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-blue-600 dark:text-blue-400"></i>
                <span class="text-blue-700 dark:text-blue-300 font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 px-5 py-4 shadow-sm">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 mt-0.5"></i>
                <div>
                    <span class="text-red-700 dark:text-red-300 font-medium block mb-1">Please fix the following errors:</span>
                    <ul class="list-disc pl-5 space-y-1 text-red-600 dark:text-red-400 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Back Button -->
    <div>
        <a href="{{ route('arthanotes.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary transition bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-2 shadow-sm hover:shadow-md">
            <i class="fas fa-arrow-left"></i> Back to Feed
        </a>
    </div>

    <!-- Main Post Card -->
    <article class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
        <!-- Post Header -->
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-sm">
                        @if ($note->user->profile_image_url)
                            <img class="w-full h-full object-cover" src="{{ $note->user->profile_image_url }}" alt="">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-teal-600 text-white font-bold text-lg">
                                {{ substr($note->user->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white text-base">
                            {{ $note->user->name }}
                            @if($note->user->is_admin)
                                <i class="fas fa-check-circle text-primary text-sm ml-1" title="Admin"></i>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $note->created_at->format('M d, Y h:i A') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($note->is_pinned)
                        <span class="inline-flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 px-2.5 py-1 rounded-md">
                            <i class="fas fa-thumbtack"></i> Pinned
                        </span>
                    @endif
                    <span class="inline-flex items-center text-[10px] uppercase tracking-wider font-bold bg-primary/10 text-primary px-2.5 py-1 rounded-md">
                        {{ str_replace('_', ' ', $note->type) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Post Content -->
        <div class="px-6 py-5">
            <h1 class="font-bold text-gray-900 dark:text-white text-2xl mb-4">{{ $note->title }}</h1>
            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                {!! $note->content !!}
            </div>

            @if($note->hashtags)
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach($note->hashtags as $tag)
                        <a href="{{ route('arthanotes.index', ['q' => $tag]) }}" class="inline-flex items-center text-xs font-medium text-primary hover:text-primary/80 bg-primary/5 hover:bg-primary/10 px-2.5 py-1.5 rounded-md transition">
                            #{{ $tag }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Image -->
        @if($note->image_path)
            <div class="relative w-full border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($note->image_path) }}" alt="ArthaNote attachment" class="w-full max-h-[500px] object-contain">
            </div>
        @endif

        <!-- Interactions -->
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                @php
                    $isLikedByUser = $note->likes->contains('user_id', auth()->id());
                @endphp
                <form method="POST" action="{{ route('arthanotes.like.toggle', $note) }}" class="arthanote-like-form" data-liked="{{ $isLikedByUser ? '1' : '0' }}">
                    @csrf
                    <button type="submit" class="arthanote-like-btn inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm border {{ $isLikedByUser ? 'border-red-200 dark:border-red-800/50 bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:border-red-200 dark:hover:border-red-800' }}">
                        <i class="fa-heart {{ $isLikedByUser ? 'fas' : 'far' }}"></i>
                        <span>Arthapurna</span>
                        <span class="arthanote-like-count bg-white/50 dark:bg-black/20 px-2 py-0.5 rounded-md ml-1">{{ $note->likes_count }}</span>
                    </button>
                </form>

                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <i class="far fa-comment"></i>
                    <span>{{ $note->all_comments_count }} comments</span>
                </div>
            </div>

            @if(auth()->id() === $note->user_id || auth()->user()?->is_admin)
                <div class="flex items-center gap-2">
                    @if(auth()->id() === $note->user_id)
                        <a href="{{ route('arthanotes.edit', $note) }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:text-primary hover:bg-primary/10 border border-transparent hover:border-primary/20 transition-all shadow-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('arthanotes.destroy', $note) }}" onsubmit="return confirm('Are you sure you want to delete this ArthaNote?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 border border-transparent hover:border-red-200 dark:hover:border-red-800/30 transition-all shadow-sm">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </article>

    <!-- Comments Section -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                <i class="fas fa-comments text-lg"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                Discussion
            </h3>
        </div>

        <div class="p-6 border-b border-gray-100 dark:border-gray-800">
            <form method="POST" action="{{ route('arthanotes.comments.store', $note) }}" class="space-y-4">
                @csrf
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 mt-1 shadow-sm">
                        @if (Auth::user()->profile_image_url)
                            <img src="{{ Auth::user()->profile_image_url }}" class="w-full h-full object-cover" alt="Profile Image">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-teal-600 text-white font-bold text-sm">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 space-y-3">
                        <textarea name="body" rows="3" class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white dark:focus:bg-gray-900 transition resize-none shadow-inner" placeholder="Share your thoughts or ask a question..." required></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 rounded-xl transition-all shadow-sm hover:shadow">
                                <i class="fas fa-paper-plane mr-2"></i> Post Comment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="p-6 bg-gray-50/30 dark:bg-gray-900/30">
            @if($note->comments->count() > 0)
                <div class="space-y-6">
                    @foreach($note->comments as $comment)
                        @include('arthanotes._comment', ['comment' => $comment, 'note' => $note, 'depth' => 0])
                    @endforeach
                </div>
            @else
                <div class="text-center py-10">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-5 text-3xl text-gray-400 shadow-inner">
                        <i class="far fa-comment-dots"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">No comments yet</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Be the first to share your thoughts, start the discussion, or ask a question!</p>
                </div>
            @endif
        </div>
    </div>
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
                const iconEl = button.querySelector('i.fa-heart');
                if (!button || !countEl || !iconEl) return;

                const isLiked = form.dataset.liked === '1';
                
                // Optimistic UI Update
                form.dataset.liked = isLiked ? '0' : '1';
                const currentCount = parseInt(countEl.textContent);
                countEl.textContent = isLiked ? (currentCount - 1) : (currentCount + 1);
                
                if (isLiked) {
                    // Unlike visual
                    button.classList.remove('border-red-200', 'dark:border-red-800/50', 'bg-red-50', 'dark:bg-red-900/20', 'text-red-500', 'dark:text-red-400');
                    button.classList.add('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-800', 'text-gray-600', 'dark:text-gray-400');
                    iconEl.classList.remove('fas');
                    iconEl.classList.add('far');
                } else {
                    // Like visual
                    button.classList.remove('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-800', 'text-gray-600', 'dark:text-gray-400');
                    button.classList.add('border-red-200', 'dark:border-red-800/50', 'bg-red-50', 'dark:bg-red-900/20', 'text-red-500', 'dark:text-red-400');
                    iconEl.classList.remove('far');
                    iconEl.classList.add('fas');
                }

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
                    form.dataset.liked = data.liked ? '1' : '0';

                } catch (error) {
                    console.error('Error toggling like:', error);
                    // Revert optimistic update
                    form.dataset.liked = isLiked ? '1' : '0';
                    countEl.textContent = currentCount;
                    if (isLiked) {
                        button.classList.add('border-red-200', 'dark:border-red-800/50', 'bg-red-50', 'dark:bg-red-900/20', 'text-red-500', 'dark:text-red-400');
                        button.classList.remove('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-800', 'text-gray-600', 'dark:text-gray-400');
                        iconEl.classList.add('fas');
                        iconEl.classList.remove('far');
                    } else {
                        button.classList.add('border-gray-200', 'dark:border-gray-700', 'bg-white', 'dark:bg-gray-800', 'text-gray-600', 'dark:text-gray-400');
                        button.classList.remove('border-red-200', 'dark:border-red-800/50', 'bg-red-50', 'dark:bg-red-900/20', 'text-red-500', 'dark:text-red-400');
                        iconEl.classList.add('far');
                        iconEl.classList.remove('fas');
                    }
                } finally {
                    button.disabled = false;
                }
            });
        });
    });
</script>
@endpush
