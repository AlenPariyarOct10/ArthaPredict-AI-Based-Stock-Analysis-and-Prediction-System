<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-destructive border border-transparent rounded-md font-semibold text-xs text-destructive-foreground uppercase tracking-widest hover:bg-destructive/80 active:bg-destructive/90 focus:outline-none focus:ring-2 focus:ring-destructive focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
