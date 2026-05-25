@extends('layouts.app')

@section('content')
    <div class="bg-gray-100 dark:bg-gray-900 min-h-screen py-4">
        <div class="max-w-2xl mx-auto px-4 space-y-4">

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div
                    class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-green-700 dark:text-green-300 text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div
                    class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-red-700 dark:text-red-300 text-sm">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Share Section - Collapsible -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm" x-data="{ expanded: false }">
                <button @click="expanded = !expanded"
                    class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex-shrink-0"></div>
                        <input type="text" placeholder="Share an ArthaNote..."
                            class="text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-full px-4 py-2 text-sm w-full cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                            @click.stop="expanded = true" readonly>
                    </div>
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transform transition"
                        :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>

                <!-- Expanded Form -->
                <div x-show="expanded" x-transition class="border-t border-gray-200 dark:border-gray-700 px-6 py-6">
                    <form method="POST" action="{{ route('arthanotes.store') }}" enctype="multipart/form-data"
                        class="space-y-4" x-data="{
                        applyFormat(cmd) {
                            document.execCommand(cmd, false, null);
                        },
                        insertLink() {
                            const url = prompt('Enter URL');
                            if (url) {
                                document.execCommand('createLink', false, url);
                            }
                        },
                        syncEditor() {
                            const editor = this.$refs.editor;
                            this.$refs.contentInput.value = editor.innerHTML;
                        }
                    }" x-on:submit="syncEditor">
                        @csrf

                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Share an ArthaNote</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Post
                                    Type</label>
                                <select name="type"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                    required>
                                    <option value="insight" @selected(old('type') === 'insight')>💡 Insight Post</option>
                                    <option value="market_analysis" @selected(old('type') === 'market_analysis')>📊 Market
                                        Analysis</option>
                                    <option value="educational_note" @selected(old('type') === 'educational_note')>📚
                                        Educational Note</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Attach
                                    Image/Chart</label>
                                <input type="file" name="image" accept="image/*"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-100 file:text-blue-700 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                placeholder="Give your ArthaNote a compelling title..."
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Content</label>
                            <div
                                class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden bg-white dark:bg-gray-700">
                                <div
                                    class="flex flex-wrap gap-2 p-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                                    <button type="button" @click="applyFormat('bold')"
                                        class="text-xs font-medium rounded px-2.5 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">𝐁</button>
                                    <button type="button" @click="applyFormat('italic')"
                                        class="text-xs font-medium rounded px-2.5 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">𝐈</button>
                                    <button type="button" @click="applyFormat('underline')"
                                        class="text-xs font-medium rounded px-2.5 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">U</button>
                                    <div class="w-px bg-gray-300 dark:bg-gray-600"></div>
                                    <button type="button" @click="applyFormat('insertUnorderedList')"
                                        class="text-xs font-medium rounded px-2.5 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">•</button>
                                    <button type="button" @click="insertLink()"
                                        class="text-xs font-medium rounded px-2.5 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition">🔗</button>
                                </div>
                                <div x-ref="editor" @contenteditable="true" @input="syncEditor"
                                    class="min-h-[120px] p-4 text-sm text-gray-900 dark:text-white focus:outline-none">
                                    {!! old('content', '<p>Start writing your ArthaNote here... #NEPSE</p>') !!}</div>
                                <input type="hidden" name="content" x-ref="contentInput" value="{{ old('content') }}">
                            </div>
                        </div>

                        @if(auth()->user()?->is_admin)
                            <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="is_pinned" value="1"
                                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-2 focus:ring-blue-500">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">📌 Pin this ArthaNote</span>
                            </label>
                        @endif

                        <div class="flex justify-between gap-3 pt-2">
                            <button type="button" @click="expanded = false"
                                class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                                Publish
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('arthanotes.index') }}" class="flex gap-3 p-4">
                    <input name="q" value="{{ request('q') }}" placeholder="Search ArthaNote, hashtags..."
                        class="flex-1 rounded-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <select name="type"
                        class="rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">All Types</option>
                        <option value="insight" @selected(request('type') === 'insight')>💡 Insight</option>
                        <option value="market_analysis" @selected(request('type') === 'market_analysis')>📊 Market Analysis
                        </option>
                        <option value="educational_note" @selected(request('type') === 'educational_note')>📚 Educational
                        </option>
                    </select>
                    <button type="submit"
                        class="rounded-full border border-gray-300 dark:border-gray-600 px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Search
                    </button>
                </form>
            </div>

            <!-- Feed -->
            <div class="space-y-4">
                @foreach($notes as $note)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition overflow-hidden">
                        <!-- Post Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3 flex-1">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex-shrink-0">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $note->user->name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $note->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($note->is_pinned)
                                        <span
                                            class="text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2.5 py-1 rounded-full">📌
                                            Pinned</span>
                                    @endif
                                    <span
                                        class="text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2.5 py-1 rounded-full">{{ str_replace('_', ' ', $note->type) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Post Content -->
                        <div class="px-6 py-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 text-lg">{{ $note->title }}</h3>
                            <div
                                class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 text-sm line-clamp-4">
                                {!! $note->content !!}</div>

                            @if($note->hashtags)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($note->hashtags as $tag)
                                        <a href="{{ route('arthanotes.index', ['hashtag' => $tag]) }}"
                                            class="text-blue-600 dark:text-blue-400 text-xs font-medium hover:underline">
                                            #{{ $tag }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Image -->
                        @if($note->image_path)
                            <div class="relative overflow-hidden bg-gray-200 dark:bg-gray-700 w-full">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($note->image_path) }}" alt="ArthaNote image"
                                    class="w-full h-64 object-cover hover:scale-105 transition duration-300">
                            </div>
                        @endif

                        <!-- Interactions -->
                        <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-3">
                                <span>❤️ {{ $note->likes_count }} Arthapurna</span>
                                <span>💬 {{ $note->all_comments_count }} Comments</span>
                            </div>

                            <div class="flex items-center gap-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                @php
                                    $isLikedByUser = $note->likes->contains('user_id', auth()->id());
                                @endphp
                                <form method="POST" action="{{ route('arthanotes.like.toggle', $note) }}"
                                    class="arthanote-like-form flex-1" data-liked="{{ $isLikedByUser ? '1' : '0' }}">
                                    @csrf
                                    <button type="submit"
                                        class="arthanote-like-btn w-full rounded-lg py-2 text-sm font-semibold transition {{ $isLikedByUser ? 'text-rose-600 dark:text-rose-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        ❤️ Arthapurna <span class="arthanote-like-count">{{ $note->likes_count }}</span>
                                    </button>
                                </form>
                                <a href="{{ route('arthanotes.show', $note) }}"
                                    class="flex-1 text-center rounded-lg py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    💬 Comment
                                </a>
                                @if(auth()->id() === $note->user_id)
                                    <form method="POST" action="{{ route('arthanotes.destroy', $note) }}"
                                        onsubmit="return confirm('Delete this ArthaNote?');" class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-full text-center rounded-lg py-2 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $notes->links() }}
            </div>
        </div>
    </div>

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
                            button.classList.remove('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
                            button.classList.add('text-rose-600', 'dark:text-rose-400');
                        } else {
                            button.classList.remove('text-rose-600', 'dark:text-rose-400');
                            button.classList.add('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
                        }
                    } catch (error) {
                        console.error('Error toggling like:', error);
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        });
    </script>
@endsection