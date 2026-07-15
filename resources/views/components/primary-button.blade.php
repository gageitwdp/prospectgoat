<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-white lp-btn-primary focus:outline-none focus:ring-2 focus:ring-[var(--lp-secondary)] focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
