@if($note->allComments->count() > 0)
    <div class="space-y-3">
        @foreach($note->allComments as $comment)
            @include('arthanotes._comment-inline', ['comment' => $comment, 'note' => $note, 'depth' => 0])
        @endforeach
    </div>
@else
    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
        <i class="far fa-comment-dots text-2xl mb-2"></i>
        <p class="text-sm">No comments yet. Be the first to comment!</p>
    </div>
@endif
