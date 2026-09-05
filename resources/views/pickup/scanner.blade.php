<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Entrega de paquetes</p>
            <h1 class="pt-1 text-2xl font-bold tracking-tight text-tik-ink">ESCANEAR CÓDIGO QR</h1>
            <p class="pt-1 text-sm text-gray-500">Apunta la cámara al código QR del cliente.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            <section data-qr-scanner class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="relative aspect-[4/3] w-full overflow-hidden bg-tik-ink sm:aspect-video">
                    <video data-scanner-video class="h-full w-full object-cover" muted playsinline></video>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="h-52 w-52 rounded-3xl border-4 border-white/90 shadow-[0_0_0_999px_rgba(0,0,0,0.28)] sm:h-64 sm:w-64"></div>
                    </div>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <p data-scanner-status aria-live="polite" class="text-center text-sm font-medium text-gray-600">Preparando cámara…</p>
                    <button data-scanner-start type="button" class="inline-flex w-full items-center justify-center rounded-xl bg-tik-red px-5 py-3.5 text-base font-bold text-white transition hover:bg-tik-red-dark disabled:cursor-wait disabled:opacity-60">Iniciar / reintentar cámara</button>
                    <form data-scanner-form method="POST" action="{{ route('pickup.resolve') }}">
                        @csrf
                        <input data-scanner-code type="hidden" name="code">
                    </form>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="font-bold text-tik-ink">Ingresar código manualmente</h2>
                <p class="mt-1 text-sm text-gray-500">Consulta el paquete usando su código de seguimiento.</p>
                <form method="POST" action="{{ route('pickup.manual') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                    @csrf
                    <label for="tracking_code" class="sr-only">Código de seguimiento</label>
                    <input id="tracking_code" name="tracking_code" type="text" value="{{ old('tracking_code') }}" placeholder="TIK-260904-0001" class="block min-h-12 flex-1 rounded-xl border-gray-300 font-mono text-sm shadow-sm focus:border-tik-red focus:ring-tik-red" required maxlength="15">
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-tik-box bg-white px-5 text-sm font-bold text-tik-ink transition hover:bg-orange-50">Buscar paquete</button>
                </form>
                <x-input-error :messages="$errors->get('tracking_code')" class="mt-2" />
            </section>
        </div>
    </div>
</x-app-layout>
