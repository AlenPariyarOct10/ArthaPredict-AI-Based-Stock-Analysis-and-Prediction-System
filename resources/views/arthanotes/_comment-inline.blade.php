<div class="rounded-lg border border-border bg-background/60 p-3 {{ $depth > 0 ? 'ml-4 mt-3' : '' }}" x-data="{
    showReplyBox: false,
    editing: false,
    editBody: `{{ $comment->body }}`
}">
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2">
            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-sm">
                @if ($comment->user->profile_image_url)
                    <img src="{{ $comment->user->profile_image_url }}" class="w-full h-full object-cover" alt="">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-teal-600 text-white font-bold text-xs">
                        {{ substr($comment->user->name, 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold">{{ $comment->user->name }}</p>
                    @if($comment->user->is_admin)
                        <i class="fas fa-check-circle text-primary text-xs" title="Admin"></i>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $comment->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        @if(auth()->id() === $comment->user_id)
            <div class="flex items-center gap-1">
                <button type="button" @click="editing = true" class="text-xs rounded-md border border-border px-2 py-1 hover:bg-muted transition text-gray-500 hover:text-primary">
                    <i class="fas fa-edit"></i>
                </button>
                <form method="POST" action="{{ route('arthanotes.comments.update', $comment) }}" class="comment-update-form" data-comment-id="{{ $comment->id }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs rounded-md border border-red-200 text-red-600 px-2 py-1 hover:bg-red-50 transition" onclick="return confirm('Update this comment?')">
                        <i class="fas fa-save"></i>
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Comment Body Display -->
    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300" x-show="!editing" x-html="`{{ $comment->body }}`"></div>

    <!-- Comment Edit Form -->
    <form method="POST" action="{{ route('arthanotes.comments.update', $comment) }}" class="mt-2 space-y-2" x-show="editing" x-transition>
        @csrf
        @method('PATCH')
        <textarea name="body" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white" required x-model="editBody" x-ref="editTextarea"></textarea>
        <input type="hidden" name="body" :value="editBody">
        <div class="flex justify-end gap-2">
            <button type="button" @click="editing = false" class="text-xs px-2 py-1 text-gray-500 hover:text-gray-700">Cancel</button>
            <button type="submit" class="text-xs px-2 py-1 bg-primary text-white rounded hover:bg-primary/90">Save</button>
        </div>
    </form>

    <!-- Reply Button -->
    <div class="mt-2 flex items-center gap-2">
        <button type="button" @click="showReplyBox = !showReplyBox" class="text-xs rounded-md border border-border px-2 py-1 hover:bg-muted transition text-gray-500 hover:text-primary">
            <i class="fas fa-reply mr-1"></i> Reply
        </button>
    </div>

    <!-- Reply Form -->
    <form method="POST" action="{{ route('arthanotes.comments.store', $note) }}" class="mt-2 space-y-2 reply-form" x-show="showReplyBox" x-transition data-parent-id="{{ $comment->id }}">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
        <textarea name="body" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary resize-none" placeholder="Write a reply..." required></textarea>
        <div class="flex justify-end">
            <button type="submit" class="text-xs rounded-md bg-primary text-white px-3 py-1 hover:bg-primary/90 transition">Post Reply</button>
        </div>
    </form>

    <!-- Nested Replies -->
    @if($depth < 2 && $comment->replies && $comment->replies->count() > 0)
        <div class="mt-3 space-y-2">
            @foreach($comment->replies as $reply)
                @include('arthanotes._comment-inline', ['comment' => $reply, 'note' => $note, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
