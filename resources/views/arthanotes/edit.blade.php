@extends('layouts.app')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6 px-4 py-6">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div
                class="rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 px-5 py-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-blue-700 dark:text-blue-300 font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 px-5 py-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <span class="text-red-700 dark:text-red-300 font-medium block mb-1">Please fix the following
                            errors:</span>
                        <ul class="list-disc pl-5 space-y-1 text-red-600 dark:text-red-400 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('arthanotes.show', $note) }}"
                    class="group inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm">
                    <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Note
                </a>
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    <span>Editing: {{ $note->title }}</span>
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <article
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-6 md:p-8">
                <div class="border-b border-gray-200 dark:border-gray-800 pb-5 mb-6">
                    <h1
                        class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-400 bg-clip-text text-transparent">
                        Edit Your ArthaNote
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Update your insights, analysis, or educational content for the community
                    </p>
                </div>

                <form method="POST" action="{{ route('arthanotes.update', $note) }}" enctype="multipart/form-data"
                    class="space-y-6" x-data="{
                              selectedType: '{{ old('type', $note->type) }}',
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
                                  this.$refs.editor.innerHTML = this.$refs.contentInput.value || '{!! addslashes(old('content', $note->content)) !!}';
                              }
                          }" @submit="syncEditor" x-init="init()">
                    @csrf
                    @method('PATCH')

                    <!-- Post Type Selection -->
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z">
                                </path>
                            </svg>
                            Post Type
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label
                                class="relative flex cursor-pointer rounded-xl border p-4 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                :class="selectedType === 'insight' ? 'border-primary bg-primary/10 dark:border-primary dark:bg-primary/20' : 'border-gray-200 dark:border-gray-700'">
                                <input type="radio" name="type" value="insight" class="sr-only" x-model="selectedType">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">💡</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Insight</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Quick market insights</div>
                                    </div>
                                </div>
                            </label>
                            <label
                                class="relative flex cursor-pointer rounded-xl border p-4 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                :class="selectedType === 'market_analysis' ? 'border-primary bg-primary/10 dark:border-primary dark:bg-primary/20' : 'border-gray-200 dark:border-gray-700'">
                                <input type="radio" name="type" value="market_analysis" class="sr-only"
                                    x-model="selectedType">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">📊</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Market Analysis</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">In-depth market analysis</div>
                                    </div>
                                </div>
                            </label>
                            <label
                                class="relative flex cursor-pointer rounded-xl border p-4 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                :class="selectedType === 'educational_note' ? 'border-primary bg-primary/10 dark:border-primary dark:bg-primary/20' : 'border-gray-200 dark:border-gray-700'">
                                <input type="radio" name="type" value="educational_note" class="sr-only"
                                    x-model="selectedType">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">📚</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">Educational</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Learn and grow</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Title
                        </label>
                        <input type="text" name="title" value="{{ old('title', $note->title) }}"
                            placeholder="Give your ArthaNote a compelling title..."
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-5 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required />
                    </div>

                    <!-- Content Editor -->
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            Content
                        </label>
                        <div
                            class="rounded-xl border border-gray-300 dark:border-gray-600 overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                            <!-- Toolbar -->
                            <div
                                class="flex flex-wrap gap-1 p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <button type="button" @click="applyFormat('bold')"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    title="Bold (Ctrl+B)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z">
                                        </path>
                                    </svg>
                                </button>
                                <button type="button" @click="applyFormat('italic')"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    title="Italic (Ctrl+I)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 4h4v4h-4z M14 16h-4v4h4z M12 8v8"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="applyFormat('underline')"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    title="Underline (Ctrl+U)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 21h14M12 3v12a4 4 0 008 0V3"></path>
                                    </svg>
                                </button>
                                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                                <button type="button" @click="applyFormat('insertUnorderedList')"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    title="Bullet List">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="applyFormat('insertOrderedList')"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    title="Numbered List">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 20h14M7 12h14M7 4h14M3 20h.01M3 12h.01M3 4h.01"></path>
                                    </svg>
                                </button>
                                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                                <button type="button" @click="insertLink()"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    title="Insert Link (Ctrl+K)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.102m1.858-2.828a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.102">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <!-- Editor Area -->
                            <div x-ref="editor" contenteditable="true"
                                class="min-h-[250px] p-4 text-sm text-gray-900 dark:text-white focus:outline-none prose prose-sm max-w-none dark:prose-invert"
                                @input="syncEditor" @keydown.ctrl.b.prevent="applyFormat('bold')"
                                @keydown.ctrl.i.prevent="applyFormat('italic')"
                                @keydown.ctrl.u.prevent="applyFormat('underline')" @keydown.ctrl.k.prevent="insertLink()">
                            </div>
                            <input type="hidden" name="content" x-ref="contentInput"
                                value="{{ old('content', $note->content) }}" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            💡 Tip: Use Ctrl+B for bold, Ctrl+I for italic, Ctrl+U for underline, and Ctrl+K for links
                        </p>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            Attach Image/Chart
                        </label>
                        <div class="relative">
                            <input type="file" name="image" accept="image/*"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-5 py-3 text-sm text-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300 transition" />
                        </div>
                        @if($note->image_path)
                            <div
                                class="mt-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800/50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Current Image:</span>
                                    <label
                                        class="inline-flex items-center gap-2 text-xs text-red-600 dark:text-red-400 cursor-pointer">
                                        <input type="checkbox" name="remove_image" value="1"
                                            class="rounded border-red-300 text-red-600 focus:ring-red-500">
                                        Remove image
                                    </label>
                                </div>
                                <img src="{{ Storage::url($note->image_path) }}" alt="Current image"
                                    class="max-h-48 w-auto rounded-lg shadow-sm" />
                            </div>
                        @endif
                    </div>

                    <!-- Admin Options -->
                    @if(auth()->user()?->is_admin)
                        <div
                            class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/30 dark:bg-amber-900/10 p-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_pinned" value="1" {{ $note->is_pinned ? 'checked' : '' }}
                                    class="w-5 h-5 rounded border-amber-300 dark:border-amber-600 text-amber-600 focus:ring-2 focus:ring-amber-500" />
                                <div>
                                    <span class="font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                        </svg>
                                        Pin this ArthaNote
                                    </span>
                                    <p class="text-xs text-amber-600 dark:text-amber-500 mt-1">Pinned notes will appear at the
                                        top of the listing page</p>
                                </div>
                            </label>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div
                        class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-800 mt-8">
                        <a href="{{ route('arthanotes.show', $note) }}"
                            class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm hover:shadow-md">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center px-8 py-3 text-sm font-semibold text-white bg-primary hover:bg-primary/80 active:bg-primary/90 rounded-xl transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            Update ArthaNote
                        </button>
                    </div>
                </form>
            </div>
        </article>
    </div>


@push('styles')
    <style>
        [contenteditable="true"]:focus {
            outline: none;
        }

        [contenteditable="true"] a {
            color: #3b82f6;
            text-decoration: underline;
        }

        [contenteditable="true"] ul,
        [contenteditable="true"] ol {
            padding-left: 1.5rem;
            margin: 0.5rem 0;
        }

        [contenteditable="true"] li {
            margin: 0.25rem 0;
        }
    </style>
@endpush

@endsection