<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm print:border-0 print:shadow-none">
                <div class="bg-red-50 px-5 py-7 text-center sm:px-8">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-tik-red text-white shadow-md shadow-red-200 print:hidden">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" /></svg>
                    </span>
                    <h1 class="mt-4 text-2xl font-bold tracking-tight text-tik-ink">{{ session('status', 'Paquete registrado correctamente') }}</h1>
                    <p class="mt-2 text-sm text-gray-600">Comparte este QR con la persona que recogerá el paquete.</p>
                </div>

                <div class="grid gap-8 p-5 sm:p-8 md:grid-cols-[minmax(0,1fr)_18rem]">
                    <div>
                        <div class="border-b border-gray-100 pb-6">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Código de seguimiento</p>
                            <p class="mt-2 font-mono text-2xl font-bold text-tik-red-dark sm:text-3xl">{{ $package->tracking_code }}</p>
                            <div class="mt-3"><x-package-status-badge :status="$package->status" /></div>
                        </div>

                        <dl class="grid grid-cols-1 gap-5 py-6 sm:grid-cols-2">
                            <div><dt class="text-sm font-medium text-gray-500">Remitente</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->sender_name }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Destinatario</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->recipient_name }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Fecha</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->received_at?->format('d/m/Y H:i') }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->status->label() }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Categoría</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->category?->name ?? 'Sin categoría' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Ubicación</dt><dd class="mt-1 font-mono font-bold text-tik-ink">{{ $package->storage_code }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Costo</dt><dd class="mt-1 font-bold text-tik-red-dark">Bs {{ number_format((float) $package->storage_price, 2) }}</dd></div>
                        </dl>
                    </div>

                    <div class="flex flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-4 text-center">
                        @if ($pickupQrDataUri)
                            <img src="{{ $pickupQrDataUri }}" alt="Código QR para recoger el paquete {{ $package->tracking_code }}" class="h-auto w-full max-w-64">
                            <p class="mt-3 text-xs leading-5 text-gray-500">El operador verificará los datos antes de confirmar la entrega.</p>
                        @else
                            <p class="text-sm font-semibold text-tik-ink">El QR solo se muestra al generarlo.</p>
                            <p class="mt-2 text-xs leading-5 text-gray-500">Puedes generar uno nuevo desde el detalle del paquete.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 border-t border-gray-100 p-5 print:hidden sm:grid-cols-2 lg:grid-cols-5 sm:p-8">
                    <a href="{{ route('packages.show', $package) }}" class="inline-flex items-center justify-center rounded-xl border border-tik-box bg-white px-4 py-3 text-sm font-semibold text-tik-ink transition hover:bg-orange-50">Ver paquete</a>
                    <a href="{{ route('packages.ticket', $package) }}" target="_blank" class="inline-flex items-center justify-center rounded-xl bg-tik-red px-4 py-3 text-sm font-semibold text-white transition hover:bg-tik-red-dark">Ver ticket PDF</a>
                    <a href="{{ route('packages.ticket.download', $package) }}" class="inline-flex items-center justify-center rounded-xl border border-tik-red px-4 py-3 text-sm font-semibold text-tik-red-dark transition hover:bg-red-50">Descargar ticket PDF</a>
                    <a href="{{ route('packages.create') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-tik-gray">Registrar otro paquete</a>
                    <a href="{{ route('packages.index') }}" class="inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-gray-600 transition hover:bg-tik-gray hover:text-tik-ink">Volver al listado</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
