@php use Illuminate\Support\Facades\Storage; @endphp
@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
        <!-- Page Header -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-800 pb-5">
            <h1 class="text-2xl md:text-3xl font-bold text-primary dark:text-primary-light">
                ArthaNotes Community
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Share insights, market analysis, and educational notes with the community.
            </p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 px-5 py-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-blue-700 dark:text-blue-300 font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 px-5 py-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
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

        <!-- Share Section - Collapsible -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden" x-data="{ expanded: false }">
            <button @click="expanded = !expanded" class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 border-2 border-white dark:border-gray-800 shadow-sm">
                        @if (Auth::user()->profile_image_url)
                            <img src="{{ Auth::user()->profile_image_url }}" class="w-full h-full object-cover" alt="Profile Image">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-teal-600 text-white font-bold text-sm">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <input type="text" placeholder="Share an ArthaNote..." class="text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-full px-5 py-2.5 text-sm w-full cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none transition border border-transparent" @click.stop="expanded = true" readonly>
                </div>
                <div class="ml-4 p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 transform transition" :class="expanded ? 'rotate-180 bg-primary/10 text-primary' : ''">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </button>

            <!-- Expanded Form -->
            <div x-show="expanded" x-collapse class="border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                <div class="px-6 py-6">
                    <form method="POST" action="{{ route('arthanotes.store') }}" enctype="multipart/form-data" class="space-y-5" x-data="{
                        selectedType: '{{ old('type', 'insight') }}',
                        applyFormat(cmd, value = null) {
                            document.execCommand(cmd, false, value);
                            this.syncEditor();
                        },
                        insertLink() {
                            const url = prompt('Enter URL:', 'https://');
                            if (url && url !== 'https://') {
                                document.execCommand('createLink', false, url);
                                this.syncEditor();
                            }
                        },
                        syncEditor() {
                            const editor = this.$refs.editor;
                            this.$refs.contentInput.value = editor.innerHTML;
                        },
                        init() {
                            this.$refs.editor.innerHTML = this.$refs.contentInput.value || '';
                        }
                    }" @submit="syncEditor" x-init="init()">
                        @csrf

                        <!-- Post Type Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Post Type</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="relative flex cursor-pointer rounded-xl border p-3 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="selectedType === 'insight' ? 'border-primary bg-primary/5 dark:border-primary dark:bg-primary/10' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="type" value="insight" class="sr-only" x-model="selectedType">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">💡</span>
                                        <div class="font-medium text-sm text-gray-900 dark:text-white">Insight</div>
                                    </div>
                                </label>
                                <label class="relative flex cursor-pointer rounded-xl border p-3 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="selectedType === 'market_analysis' ? 'border-primary bg-primary/5 dark:border-primary dark:bg-primary/10' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="type" value="market_analysis" class="sr-only" x-model="selectedType">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📊</span>
                                        <div class="font-medium text-sm text-gray-900 dark:text-white">Analysis</div>
                                    </div>
                                </label>
                                <label class="relative flex cursor-pointer rounded-xl border p-3 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="selectedType === 'educational_note' ? 'border-primary bg-primary/5 dark:border-primary dark:bg-primary/10' : 'border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="type" value="educational_note" class="sr-only" x-model="selectedType">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📚</span>
                                        <div class="font-medium text-sm text-gray-900 dark:text-white">Education</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Title & Image -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" placeholder="Enter a compelling title..." class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Attach Image/Chart</label>
                                <input type="file" name="image" accept="image/*" class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition">
                            </div>
                        </div>

                        <!-- Content Editor -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Content</label>
                            <div class="rounded-xl border border-gray-300 dark:border-gray-600 overflow-hidden bg-white dark:bg-gray-800 shadow-sm focus-within:ring-2 focus-within:ring-primary focus-within:border-transparent transition">
                                <div class="flex flex-wrap gap-1 p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                    <button type="button" @click="applyFormat('bold')" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition" title="Bold"><i class="fas fa-bold fa-fw text-sm"></i></button>
                                    <button type="button" @click="applyFormat('italic')" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition" title="Italic"><i class="fas fa-italic fa-fw text-sm"></i></button>
                                    <button type="button" @click="applyFormat('underline')" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition" title="Underline"><i class="fas fa-underline fa-fw text-sm"></i></button>
                                    <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1 my-auto"></div>
                                    <button type="button" @click="applyFormat('insertUnorderedList')" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition" title="Bullet List"><i class="fas fa-list-ul fa-fw text-sm"></i></button>
                                    <button type="button" @click="applyFormat('insertOrderedList')" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition" title="Numbered List"><i class="fas fa-list-ol fa-fw text-sm"></i></button>
                                    <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1 my-auto"></div>
                                    <button type="button" @click="insertLink()" class="p-1.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition" title="Insert Link"><i class="fas fa-link fa-fw text-sm"></i></button>
                                </div>
                                <div x-ref="editor" contenteditable="true" @input="syncEditor" @keydown.ctrl.b.prevent="applyFormat('bold')" @keydown.ctrl.i.prevent="applyFormat('italic')" @keydown.ctrl.u.prevent="applyFormat('underline')" @keydown.ctrl.k.prevent="insertLink()" class="min-h-[150px] p-4 text-sm text-gray-900 dark:text-white focus:outline-none prose prose-sm max-w-none dark:prose-invert"></div>
                                <input type="hidden" name="content" x-ref="contentInput" value="{{ old('content', '<p>Start writing your ArthaNote here... #NEPSE</p>') }}">
                            </div>
                        </div>

                        @if(auth()->user()?->is_admin)
                            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/30 dark:bg-amber-900/10 p-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_pinned" value="1" class="w-4 h-4 rounded border-amber-300 dark:border-amber-600 text-amber-600 focus:ring-2 focus:ring-amber-500">
                                    <span class="text-sm font-semibold text-amber-700 dark:text-amber-400">📌 Pin this ArthaNote</span>
                                </label>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="expanded = false" class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition">
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 active:bg-primary/90 rounded-xl transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i> Publish Note
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-2">
            <form method="GET" action="{{ route('arthanotes.index') }}" class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input name="q" value="{{ request('q') }}" placeholder="Search ArthaNotes, hashtags..." class="w-full rounded-xl border-0 bg-gray-50 dark:bg-gray-800 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white dark:focus:bg-gray-900 transition">
                </div>
                <div class="flex gap-2">
                    <select name="type" class="rounded-xl border-0 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white dark:focus:bg-gray-900 transition min-w-[140px]">
                        <option value="">All Types</option>
                        <option value="insight" @selected(request('type') === 'insight')>💡 Insight</option>
                        <option value="market_analysis" @selected(request('type') === 'market_analysis')>📊 Analysis</option>
                        <option value="educational_note" @selected(request('type') === 'educational_note')>📚 Education</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Feed -->
        <div class="space-y-6 pb-12">
            @forelse($notes as $note)
                <article class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 hover:shadow-md transition-shadow overflow-hidden group">
                    <!-- Post Header -->
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    @if ($note->user->profile_image_url)
                                        <img class="w-full h-full object-cover" src="{{ $note->user->profile_image_url }}" alt="">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-teal-600 text-white font-bold text-sm">
                                            {{ substr($note->user->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm hover:text-primary transition cursor-pointer">
                                        {{ $note->user->name }}
                                        @if($note->user->is_admin)
                                            <i class="fas fa-check-circle text-primary text-xs ml-1" title="Admin"></i>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $note->created_at->diffForHumans() }}
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
                    <a href="{{ route('arthanotes.show', $note) }}" class="block px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-2 group-hover:text-primary transition">{{ $note->title }}</h3>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300 line-clamp-3">
                            {!! strip_tags($note->content, '<p><br><b><strong><i><em><u><ul><ol><li>') !!}
                        </div>
                    </a>

                    @if($note->hashtags)
                        <div class="px-5 pb-3 flex flex-wrap gap-1.5">
                            @foreach($note->hashtags as $tag)
                                <a href="{{ route('arthanotes.index', ['q' => $tag]) }}" class="inline-flex items-center text-xs font-medium text-primary hover:text-primary/80 bg-primary/5 hover:bg-primary/10 px-2 py-1 rounded-md transition">
                                    #{{ $tag }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- Image -->
                    @if($note->image_path)
                        <div class="relative w-full border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                            <a href="{{ route('arthanotes.show', $note) }}" class="block">
                                <img src="{{ Storage::url($note->image_path) }}" alt="ArthaNote attachment" class="w-full max-h-96 object-contain">
                            </a>
                        </div>
                    @endif

                     <!-- Interactions -->
                    <div class="px-5 py-3 border-t border-gray-100 w-full dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                        <div class="flex items-center gap-1 sm:gap-2">
                            @php
                                $isLikedByUser = $note->likes->contains('user_id', auth()->id());
                            @endphp
                            <form method="POST" action="{{ route('arthanotes.like.toggle', $note) }}" class="arthanote-like-form" data-liked="{{ $isLikedByUser ? '1' : '0' }}">
                                @csrf
                                <button type="submit" class="arthanote-like-btn inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors hover:bg-red-50 dark:hover:bg-red-900/20 {{ $isLikedByUser ? 'text-red-500 dark:text-red-400' : 'text-gray-600 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400' }}">
                                    <i class="fa-heart {{ $isLikedByUser ? 'fas' : 'far' }}"></i>
                                    <span class="hidden sm:inline">Arthapurna</span>
                                    <span class="arthanote-like-count bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full ml-1">{{ $note->likes_count }}</span>
                                </button>
                            </form>

                            <button type="button" @click="toggleComments({{ $note->id }})" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-primary hover:bg-primary/5 transition-colors comment-toggle-btn" data-note-id="{{ $note->id }}">
                                <i class="far fa-comment"></i>
                                <span class="hidden sm:inline">Comment</span>
                                <span class="bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full ml-1">{{ $note->all_comments_count }}</span>
                            </button>
                        </div>

                        @if(auth()->id() === $note->user_id || auth()->user()?->is_admin)
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.away="open = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" x-transition class="absolute right-0 bottom-full mb-2 w-36 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-10" style="display: none;">
                                    @if(auth()->id() === $note->user_id)
                                        <a href="{{ route('arthanotes.edit', $note) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <i class="fas fa-edit w-4 mr-2 text-gray-400"></i> Edit Note
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('arthanotes.destroy', $note) }}" onsubmit="return confirm('Delete this ArthaNote?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <i class="fas fa-trash-alt w-4 mr-2"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                    <!-- Inline Comments Section -->
                    <div class="comment-section hidden w-full max-w-3xl mx-auto" id="commentSection-{{ $note->id }}" x-data="{
                        newComment: '',
                        replyText: '',
                        replyingTo: null,
                        showReplyBox: {},
                        loading: false
                    }">
                        <div class="border-t border-gray-100 w-full dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                            <!-- Comment Form -->
                            <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                                <form method="POST" action="{{ route('arthanotes.comments.store', $note) }}" class="space-y-3 comment-form-ajax" data-note-id="{{ $note->id }}">
                                    @csrf
                                    <div class="flex gap-3">
                                        <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-sm">
                                            @if (Auth::user()->profile_image_url)
                                                <img src="{{ Auth::user()->profile_image_url }}" class="w-full h-full object-cover" alt="Profile Image">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-teal-600 text-white font-bold text-xs">
                                                    {{ substr(Auth::user()->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 space-y-2">
                                            <textarea name="body" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white dark:focus:bg-gray-900 transition resize-none shadow-inner" placeholder="Write a comment..." required></textarea>
                                            <div class="flex justify-end">
                                                <button type="submit" class="inline-flex items-center px-4 py-1.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 rounded-lg transition-all shadow-sm" :disabled="loading">
                                                    <span x-show="!loading">Post</span>
                                                    <span x-show="loading">Posting...</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Comments List -->
                            <div class="p-4 max-h-96 overflow-y-auto comment-list" id="commentList-{{ $note->id }}">
                                @if($note->allComments->count() > 0)
                                    <div class="space-y-3">
                                        @foreach($note->allComments->take(5) as $comment)
                                            @include('arthanotes._comment-inline', ['comment' => $comment, 'note' => $note, 'depth' => 0])
                                        @endforeach
                                        @if($note->allComments->count() > 5)
                                            <button class="text-xs text-primary hover:text-primary/80 font-medium" onclick="loadMoreComments({{ $note->id }})">
                                                Load more comments...
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                                        <i class="far fa-comment-dots text-2xl mb-2"></i>
                                        <p class="text-sm">No comments yet. Be the first to comment!</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- End Inline Comments Section -->
                </article>
            @empty
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        📝
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">No ArthaNotes found</h3>
                    <p class="text-gray-500 dark:text-gray-400">Be the first to share an insight with the community!</p>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($notes->hasPages())
                <div class="mt-8">
                    {{ $notes->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('styles')
        <style>
            [contenteditable="true"]:focus { outline: none; }
            [contenteditable="true"] a { color: var(--primary); text-decoration: underline; }
            [contenteditable="true"] ul, [contenteditable="true"] ol { padding-left: 1.5rem; margin: 0.5rem 0; }
            [contenteditable="true"] li { margin: 0.25rem 0; }
        </style>
    @endpush

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Toggle comments section
            window.toggleComments = function(noteId) {
                const section = document.getElementById('commentSection-' + noteId);
                if (section) {
                    section.classList.toggle('hidden');
                    section.classList.toggle('flex');
                }
            };

            // Like toggle
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
                        button.classList.remove('text-red-500', 'dark:text-red-400');
                        button.classList.add('text-gray-600', 'dark:text-gray-400', 'hover:text-red-500', 'dark:hover:text-red-400');
                        iconEl.classList.remove('fas');
                        iconEl.classList.add('far');
                    } else {
                        // Like visual
                        button.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:text-red-500', 'dark:hover:text-red-400');
                        button.classList.add('text-red-500', 'dark:text-red-400');
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
                        // Sync with server response
                        countEl.textContent = data.likes_count;
                        form.dataset.liked = data.liked ? '1' : '0';

                    } catch (error) {
                        console.error('Error toggling like:', error);
                        // Revert optimistic update
                        form.dataset.liked = isLiked ? '1' : '0';
                        countEl.textContent = currentCount;
                        if (isLiked) {
                            button.classList.add('text-red-500', 'dark:text-red-400');
                            button.classList.remove('text-gray-600', 'dark:text-gray-400');
                            iconEl.classList.add('fas');
                            iconEl.classList.remove('far');
                        } else {
                            button.classList.remove('text-red-500', 'dark:text-red-400');
                            button.classList.add('text-gray-600', 'dark:text-gray-400');
                            iconEl.classList.remove('fas');
                            iconEl.classList.add('far');
                        }
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            // Comment form submission (AJAX)
            document.querySelectorAll('.comment-form-ajax').forEach((form) => {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const button = form.querySelector('button[type="submit"]');
                    const textarea = form.querySelector('textarea[name="body"]');
                    const noteId = form.dataset.noteId;
                    const commentList = document.getElementById('commentList-' + noteId);

                    if (!textarea.value.trim()) return;

                    button.disabled = true;
                    button.innerHTML = '<span>Posting...</span>';

                    try {
                        const formData = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (response.ok) {
                            textarea.value = '';
                            // Reload comments or add the new comment to the list
                            loadComments(noteId);
                        }
                    } catch (error) {
                        console.error('Error posting comment:', error);
                    } finally {
                        button.disabled = false;
                        button.innerHTML = '<span>Post</span>';
                    }
                });
            });

            // Reply form submission (AJAX)
            document.querySelectorAll('.reply-form').forEach((form) => {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const button = form.querySelector('button[type="submit"]');
                    const textarea = form.querySelector('textarea[name="body"]');
                    const parentId = form.dataset.parentId;

                    if (!textarea.value.trim()) return;

                    button.disabled = true;

                    try {
                        const formData = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (response.ok) {
                            textarea.value = '';
                            // Close reply box and refresh comments
                            const noteId = form.closest('.comment-section').id.split('commentSection-')[1];
                            loadComments(noteId);
                        }
                    } catch (error) {
                        console.error('Error posting reply:', error);
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        });

        // Load comments for a note
        async function loadComments(noteId) {
            const commentList = document.getElementById('commentList-' + noteId);
            if (!commentList) return;

            try {
                const response = await fetch('/arthanotes/' + noteId + '/comments');
                if (response.ok) {
                    const html = await response.text();
                    commentList.innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        }
    </script>
@endsection
