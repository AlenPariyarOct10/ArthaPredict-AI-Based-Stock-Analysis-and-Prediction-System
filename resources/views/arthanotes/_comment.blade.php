<div class="rounded-lg border border-border bg-background/60 p-3 {{ $depth > 0 ? 'ml-4 mt-3' : '' }}">
    <div class="flex items-center justify-between gap-2">
        <div>
            <p class="text-sm font-semibold">{{ $comment->user->name }}</p>
            <p class="text-xs text-muted-foreground">{{ $comment->created_at->diffForHumans() }}</p>
        </div>
    </div>

    <p class="mt-2 text-sm">{{ $comment->body }}</p>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="text-xs rounded-md border border-border px-2 py-1 hover:bg-muted transition"
            x-on:click="$refs['replyBox{{ $comment->id }}'].classList.toggle('hidden')"
        >
            Reply
        </button>

        @if(auth()->id() === $comment->user_id)
            <button
                type="button"
                class="text-xs rounded-md border border-border px-2 py-1 hover:bg-muted transition"
                x-on:click="$refs['editBox{{ $comment->id }}'].classList.toggle('hidden')"
            >
                Edit
            </button>

            <form method="POST" action="{{ route('arthanotes.comments.destroy', $comment) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs rounded-md border border-red-200 text-red-600 px-2 py-1 hover:bg-red-50 transition">
                    Delete
                </button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('arthanotes.comments.store', $note) }}" class="mt-3 hidden" x-ref="replyBox{{ $comment->id }}">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
        <textarea name="body" rows="2" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="Write a reply..." required></textarea>
        <div class="mt-2 flex justify-end">
            <button type="submit" class="text-xs rounded-md gradient-accent text-white px-3 py-1.5">Post Reply</button>
        </div>
    </form>

    @if(auth()->id() === $comment->user_id)
        <form method="POST" action="{{ route('arthanotes.comments.update', $comment) }}" class="mt-3 hidden" x-ref="editBox{{ $comment->id }}">
            @csrf
            @method('PATCH')
            <textarea name="body" rows="2" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" required>{{ $comment->body }}</textarea>
            <div class="mt-2 flex justify-end">
                <button type="submit" class="text-xs rounded-md gradient-accent text-white px-3 py-1.5">Save</button>
            </div>
        </form>
    @endif

    @if($depth < 2)
        @foreach($comment->replies as $reply)
            @include('arthanotes._comment', ['comment' => $reply, 'note' => $note, 'depth' => $depth + 1])
        @endforeach
    @endif
</div>
