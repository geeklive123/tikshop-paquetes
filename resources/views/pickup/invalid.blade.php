<x-app-layout>
    <div class="py-10 sm:py-14">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-red-200 bg-white p-6 text-center shadow-sm sm:p-8">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-2xl font-bold text-tik-red-dark">!</span>
                <h1 class="mt-4 text-2xl font-bold text-tik-ink">No se puede usar este QR</h1>
                <p class="mt-3 text-base text-gray-600">{{ $message }}</p>
                <a href="{{ route('pickup.scanner') }}" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-tik-red px-6 text-sm font-bold text-white transition hover:bg-tik-red-dark">Volver a escanear</a>
            </section>
        </div>
    </div>
</x-app-layout>
