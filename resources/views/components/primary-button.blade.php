<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary w-full justify-center py-3']) }}>
    {{ $slot }}
</button>
