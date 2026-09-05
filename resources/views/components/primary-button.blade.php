<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg border border-transparent bg-tik-red px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-tik-red-dark focus:bg-tik-red-dark focus:outline-none focus:ring-2 focus:ring-tik-red focus:ring-offset-2 active:bg-tik-red-dark']) }}>
    {{ $slot }}
</button>
