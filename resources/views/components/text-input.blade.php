@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-input border-border text-foreground placeholder-muted-foreground focus:border-primary focus:ring-primary rounded-md shadow-sm']) }}>
