<section>
    <header>
        <h2 class="text-lg font-medium text-foreground">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-muted-foreground">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- Profile Picture Section -->
        <div class="flex flex-col sm:flex-row items-center gap-6 pb-6 border-b border-border/80">
            <div class="relative group">
                <!-- Image Preview Container -->
                <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-blue-500/30 group-hover:border-blue-500 shadow-md transition duration-300 bg-slate-100 dark:bg-slate-800">
                    <img id="avatar-preview" 
                         src="{{ $user->profile_image_url }}" 
                         alt="{{ $user->name }}" 
                         class="w-full h-full object-cover"
                         data-default-avatar="{{ $user->profile_image_url }}"
                         data-initials-avatar="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=0d9488&background=f0fdf4&bold=true">
                </div>
                <!-- Mini Edit Icon Overlay -->
                <div class="absolute inset-0 bg-slate-900/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex-1 text-center sm:text-left space-y-3">
                <x-input-label :value="__('Profile Photo')" class="text-sm font-semibold" />
                <p class="text-xs text-muted-foreground">
                    PNG, JPG, JPEG, WEBP or GIF up to 2MB.
                </p>
                
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                    <!-- Custom File Upload Button -->
                    <label for="profile_image" class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-500 dark:hover:bg-blue-600 text-xs font-semibold rounded-lg shadow-sm transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        {{ __('Choose Photo') }}
                    </label>
                    <input type="file" id="profile_image" name="profile_image" class="hidden" accept="image/*" onchange="previewImage(this)">

                    <!-- Remove Button -->
                    <button type="button" 
                            id="btn-remove-avatar"
                            onclick="removeAvatar()" 
                            class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold rounded-lg transition-colors duration-200 {{ !$user->profile_image ? 'hidden' : '' }}">
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('Remove') }}
                    </button>
                    
                    <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
                </div>
                
                <!-- Display image error if validation failed -->
                <x-input-error class="mt-2" :messages="$errors->get('profile_image')" />
                
                <!-- Pending changes notification -->
                <p id="avatar-pending-status" class="text-xs font-medium text-amber-500 dark:text-amber-400 hidden mt-1">
                    {{ __('Changes are pending. Click Save to apply.') }}
                </p>
            </div>
        </div>

        <script>
            function previewImage(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('avatar-preview');
                        preview.src = e.target.result;
                        
                        // Mark remove_profile_image as false since a new file is uploaded
                        document.getElementById('remove_profile_image').value = '0';
                        
                        // Show pending status
                        document.getElementById('avatar-pending-status').innerText = 'New image chosen. Click Save to upload.';
                        document.getElementById('avatar-pending-status').classList.remove('hidden');
                        
                        // Show remove button in case it was hidden
                        document.getElementById('btn-remove-avatar').classList.remove('hidden');
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function removeAvatar() {
                const preview = document.getElementById('avatar-preview');
                const initialsAvatar = preview.getAttribute('data-initials-avatar');
                
                // Clear the input file
                document.getElementById('profile_image').value = '';
                
                // Set the input field to remove the avatar
                document.getElementById('remove_profile_image').value = '1';
                
                // Change the preview back to initials
                preview.src = initialsAvatar;
                
                // Show pending status
                document.getElementById('avatar-pending-status').innerText = 'Avatar will be removed. Click Save to apply.';
                document.getElementById('avatar-pending-status').classList.remove('hidden');
                
                // Hide the remove button
                document.getElementById('btn-remove-avatar').classList.add('hidden');
            }
        </script>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-foreground">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-muted-foreground hover:text-foreground rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-muted-foreground"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
