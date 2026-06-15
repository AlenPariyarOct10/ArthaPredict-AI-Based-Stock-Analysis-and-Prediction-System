@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-foreground">Logo Settings</h1>
        <p class="text-sm text-muted-foreground mt-1">Manage your application logo displayed across the platform.</p>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Logo Preview -->
        <div class="bg-card dark:bg-card rounded-xl shadow-sm border border-border dark:border-border p-6">
            <h2 class="text-lg font-semibold mb-4">Current Logo</h2>
            <div class="flex flex-col items-center justify-center p-8 bg-muted/50 dark:bg-secondary/50 rounded-lg">
                <img src="{{ \App\Models\AppSetting::getLogoUrl() }}"
                     alt="Current Logo"
                     class="h-20 w-auto object-contain mb-4">
                <p class="text-sm text-muted-foreground text-center">
                    Current logo displayed in the application
                </p>
            </div>
        </div>

        <!-- Upload New Logo -->
        <div class="bg-card dark:bg-card rounded-xl shadow-sm border border-border dark:border-border p-6">
            <h2 class="text-lg font-semibold mb-4">Upload New Logo</h2>

            <form action="{{ route('admin.logo.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="logo" class="block text-sm font-medium text-foreground mb-2">
                        Select Logo Image
                    </label>
                    <input type="file" id="logo" name="logo" accept="image/*"
                           onchange="previewImage(event)"
                           class="w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0 file:text-sm file:font-medium
                                  file:bg-primary file:text-primary-foreground
                                  hover:file:bg-primary/90 cursor-pointer">
                    <p class="text-xs text-muted-foreground mt-1">
                        Supported formats: PNG, JPG, JPEG, SVG. Max size: 2MB
                    </p>
                    @error('logo')
                        <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Image Preview -->
                <div id="preview-container" class="hidden">
                    <label class="block text-sm font-medium text-foreground mb-2">Image Preview</label>
                    <div class="flex items-center justify-center p-4 bg-muted/50 dark:bg-secondary/50 rounded-lg">
                        <img id="image-preview" class="max-h-32 w-auto object-contain" src="" alt="Preview">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 text-white gradient-accent hover:opacity-90 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition shadow-sm">
                        Upload Logo
                    </button>
                    <a href="{{ route('admin.logo.reset') }}"
                       onclick="return confirm('Are you sure you want to reset to the default logo?')"
                       class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Reset
                    </a>
                </div>
            </form>

            <script>
                function previewImage(event) {
                    const file = event.target.files[0];
                    const previewContainer = document.getElementById('preview-container');
                    const preview = document.getElementById('image-preview');

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            previewContainer.classList.remove('hidden');
                            previewContainer.classList.add('flex');
                        }
                        reader.readAsDataURL(file);
                    } else {
                        previewContainer.classList.add('hidden');
                        preview.src = '';
                    }
                }
            </script>
        </div>
    </div>

    <!-- Logo Requirements -->
    <div class="bg-card dark:bg-card rounded-xl shadow-sm border border-border dark:border-border p-6">
        <h2 class="text-lg font-semibold mb-4">Logo Requirements</h2>
        <ul class="text-sm text-muted-foreground space-y-2">
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-green-500 mt-0.5"></i>
                <span>Recommended size: 32x32 pixels or larger for crisp display</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-green-500 mt-0.5"></i>
                <span>Transparency is supported (PNG recommended)</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-green-500 mt-0.5"></i>
                <span>The logo will be displayed in the sidebar, landing page, and browser tab</span>
            </li>
        </ul>
    </div>
</div>
@endsection
