<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Consulta manual</p>
            <h1 class="pt-1 text-2xl font-bold tracking-tight text-tik-ink">Resultado de búsqueda</h1>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                @if ($package)
                    <div class="flex flex-col gap-3 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-mono text-2xl font-bold text-tik-red-dark">{{ $package->tracking_code }}</p>
                        <x-package-status-badge :status="$package->status" />
                    </div>
                    <dl class="grid grid-cols-1 gap-5 py-6 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-gray-500">Destinatario</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->recipient_name }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Celular</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->recipient_phone }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Remitente</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->sender_name }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Descripción</dt><dd class="mt-1 text-sm text-tik-ink">{{ $package->description ?: 'Sin descripción' }}</dd></div>
                    </dl>
                    <p class="rounded-xl bg-orange-50 px-4 py-3 text-sm text-amber-900">La búsqueda manual es solo informativa. Para entregar, escanea un QR válido.</p>
                @else
                    <div class="py-6 text-center">
                        <h2 class="text-xl font-bold text-tik-ink">Paquete no encontrado</h2>
                        <p class="mt-2 text-sm text-gray-500">Revisa el código e inténtalo nuevamente.</p>
                    </div>
                @endif
                <a href="{{ route('pickup.scanner') }}" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-gray-300 px-5 text-sm font-bold text-gray-700 transition hover:bg-tik-gray">Volver al escáner</a>
            </section>
        </div>
    </div>
</x-app-layout>
