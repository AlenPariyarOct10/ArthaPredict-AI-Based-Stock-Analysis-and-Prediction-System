<?php

namespace App\Http\Controllers;

use App\Models\ArthaNote;
use App\Models\ArthaNoteComment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArthaNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = ArthaNote::query()
            ->with([
                'user',
                'likes',
                'allComments.user',
                'allComments.replies.user',
            ])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('is_pinned')
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('hashtag')) {
            $hashtag = ltrim((string) $request->input('hashtag'), '#');
            $query->whereJsonContains('hashtags', $hashtag);
        }

        if ($request->filled('q')) {
            $term = (string) $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
            });
        }

        $notes = $query->paginate(10)->withQueryString();

        $trendingNotes = ArthaNote::query()
            ->with('user')
            ->withCount(['likes', 'allComments'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('likes_count')
            ->orderByDesc('all_comments_count')
            ->take(5)
            ->get();

        return view('arthanotes.index', compact('notes', 'trendingNotes'));
    }

    public function show(ArthaNote $note)
    {
        $note->load([
            'user',
            'likes',
            'comments.user',
            'comments.replies.user',
        ])->loadCount(['likes', 'allComments']);

        return view('arthanotes.show', compact('note'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:insight,market_analysis,educational_note',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:4096',
            'is_pinned' => 'nullable|boolean',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('arthanotes', 'public')
            : null;

        preg_match_all('/#([A-Za-z0-9_]+)/', $validated['content'], $matches);
        $hashtags = collect($matches[1] ?? [])
            ->map(fn ($tag) => strtolower((string) $tag))
            ->unique()
            ->values()
            ->all();

        ArthaNote::create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $this->sanitizeRichText($validated['content']),
            'image_path' => $imagePath,
            'is_pinned' => auth()->user()?->is_admin ? (bool) ($validated['is_pinned'] ?? false) : false,
            'hashtags' => $hashtags,
        ]);

        return back()->with('success', 'ArthaNote published successfully.');
    }

    public function toggleLike(ArthaNote $note)
    {
        $existingLike = $note->likes()->where('user_id', auth()->id())->first();
        $liked = false;

        if ($existingLike) {
            $existingLike->delete();
        } else {
            $note->likes()->create(['user_id' => auth()->id()]);
            $liked = true;
        }

        $likesCount = $note->likes()->count();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $likesCount,
            ]);
        }

        return back();
    }

    public function destroy(ArthaNote $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $note->delete();

        return back()->with('success', 'ArthaNote deleted successfully.');
    }

    // Show edit form for authors
    public function edit(ArthaNote $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);
        $note->load(['user', 'likes', 'comments.user', 'comments.replies.user']);
        return view('arthanotes.edit', compact('note'));
    }

    // Update note after edit
    public function update(Request $request, ArthaNote $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'type' => 'required|in:insight,market_analysis,educational_note',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:4096',
            'is_pinned' => 'nullable|boolean',
        ]);

        // Handle image replacement
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($note->image_path) {
                \Illuminate\Support\Facades\Storage::delete($note->image_path);
            }
            $imagePath = $request->file('image')->store('arthanotes', 'public');
        } else {
            $imagePath = $note->image_path;
        }

        // Extract hashtags from content
        preg_match_all('/#([A-Za-z0-9_]+)/', $validated['content'], $matches);
        $hashtags = collect($matches[1] ?? [])
            ->map(fn ($tag) => strtolower((string) $tag))
            ->unique()
            ->values()
            ->all();

        $note->update([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $this->sanitizeRichText($validated['content']),
            'image_path' => $imagePath,
            'is_pinned' => auth()->user()?->is_admin ? (bool) ($validated['is_pinned'] ?? false) : $note->is_pinned,
            'hashtags' => $hashtags,
        ]);

        return redirect()->route('arthanotes.show', $note)->with('success', 'ArthaNote updated successfully.');
    }

    public function storeComment(Request $request, ArthaNote $note)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:artha_note_comments,id',
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = ArthaNoteComment::where('id', $validated['parent_id'])
                ->where('artha_note_id', $note->id)
                ->firstOrFail();

            $parentId = $parent->id;
        } else {
            $parentId = null;
        }

        $note->allComments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'body' => trim($validated['body']),
        ]);

        return back()->with('success', 'Comment added.');
    }

    /**
     * Load comments for a note (AJAX response).
     */
    public function loadComments(ArthaNote $note)
    {
        $note->load([
            'allComments.user',
            'allComments.replies.user',
        ]);

        return view('arthanotes._comments-list', compact('note'));
    }

    public function updateComment(Request $request, ArthaNoteComment $comment)
    {
        abort_unless($comment->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment->update([
            'body' => trim($validated['body']),
        ]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroyComment(ArthaNoteComment $comment)
    {
        abort_unless($comment->user_id === auth()->id(), 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    private function sanitizeRichText(string $content): string
    {
        $allowedTags = '<p><br><b><strong><i><em><u><ul><ol><li><blockquote><a><h1><h2><h3>';
        $clean = strip_tags($content, $allowedTags);

        // Ensure links remain safe in user generated HTML.
        $clean = preg_replace('/<a\s+([^>]*href=["\'])(?!https?:\/\/|mailto:)([^"\']*)(["\'][^>]*)>/i', '<a $1#$3>', $clean) ?? $clean;

        return Str::of($clean)->trim()->toString();
    }
}
