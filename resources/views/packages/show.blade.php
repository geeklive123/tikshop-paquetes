<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-sm font-semibold text-tik-red">Detalle del paquete</p><h1 class="mt-1 font-mono text-2xl font-bold tracking-tight text-tik-ink">{{ $package->tracking_code }}</h1></div>
            <a href="{{ route('packages.index') }}" class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>
            @endif
            <x-input-error :messages="$errors->get('printer')" />
            <x-input-error :messages="$errors->get('print_job')" />
            <x-input-error :messages="$errors->get('package')" />
            <x-input-error :messages="$errors->get('pickup_qr')" />
            <x-input-error :messages="$errors->get('reason')" />

            <section class="rounded-2xl border border-gray-200 border-l-4 border-l-tik-red bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Código de seguimiento</p><p class="mt-2 font-mono text-2xl font-bold text-tik-red-dark sm:text-3xl">{{ $package->tracking_code }}</p></div>
                    <x-package-status-badge :status="$package->status" />
                </div>
                <dl class="mt-6 grid grid-cols-1 gap-5 border-t border-gray-100 pt-5 sm:grid-cols-3">
                    <div><dt class="text-sm font-medium text-gray-500">Fecha de recepción</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->received_at?->format('d/m/Y H:i') ?? 'Sin registrar' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Recibido por</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->receivedBy?->name ?? 'Sin registrar' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Sucursal</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->branch?->name ?? 'Sin registrar' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Categoría / tamaño</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->category?->name ?? 'Sin categoría' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Código / ubicación</dt><dd class="mt-1 font-mono text-sm font-bold text-tik-ink">{{ $package->storage_code ?? 'Sin registrar' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Costo de almacenaje</dt><dd class="mt-1 text-sm font-bold text-tik-red-dark">{{ $package->storage_price === null ? 'Sin registrar' : 'Bs '.number_format((float) $package->storage_price, 2) }}</dd></div>
                </dl>

                @if ($package->status === \App\Enums\PackageStatus::Cancelled)
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                        <p class="font-bold">Paquete anulado</p>
                        <p class="mt-1"><span class="font-semibold">Motivo:</span> {{ $package->cancellation_reason ?: 'Sin motivo registrado' }}</p>
                        <p class="mt-1 text-xs text-red-700">{{ $package->cancelled_at?->format('d/m/Y H:i') }} · {{ $package->cancelledBy?->name ?? 'Usuario no disponible' }}</p>
                    </div>
                @endif

                @if ($package->status === \App\Enums\PackageStatus::ReadyForPickup)
                    @can('deliver', $package)
                        <form method="POST" action="{{ route('packages.deliver', $package) }}" class="mt-6 border-t border-gray-100 pt-5">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-tik-red px-5 py-3 text-sm font-bold text-white transition hover:bg-tik-red-dark sm:w-auto">CONFIRMAR ENTREGA</button>
                        </form>
                    @endcan
                @endif

                @if (! in_array($package->status, [\App\Enums\PackageStatus::Delivered, \App\Enums\PackageStatus::Cancelled], true))
                    @can('generatePickupToken', $package)
                        <form method="POST" action="{{ route('packages.regenerate-qr', $package) }}" class="mt-6 border-t border-gray-100 pt-5">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-tik-red px-5 py-3 text-sm font-semibold text-white transition hover:bg-tik-red-dark sm:w-auto">Regenerar QR</button>
                            <p class="mt-2 text-xs text-gray-500">El código anterior dejará de funcionar inmediatamente.</p>
                        </form>
                    @endcan
                @endif
                <div class="mt-6 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:flex-wrap">
                    @can('update', $package)
                        <a href="{{ route('packages.edit', $package) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Editar paquete</a>
                    @endcan
                    @if (! in_array($package->status, [\App\Enums\PackageStatus::Delivered, \App\Enums\PackageStatus::Cancelled], true))
                        @if ($defaultPrinter)
                            <form method="POST" action="{{ route('packages.print-ticket', $package) }}">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-tik-red px-5 py-3 text-sm font-semibold text-white hover:bg-tik-red-dark">Imprimir ticket</button>
                            </form>
                        @endif
                        <a href="{{ route('packages.ticket', $package) }}" target="_blank" class="inline-flex items-center justify-center rounded-xl border border-tik-red px-5 py-3 text-sm font-semibold text-tik-red-dark hover:bg-red-50">Ver PDF</a>
                        <a href="{{ route('packages.ticket.download', $package) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Descargar</a>
                        @if ($hasShareablePickupQr)
                            @can('sharePickupQr', $package)
                                <a href="{{ route('packages.pickup-qr.download', $package) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Descargar QR</a>
                                <button
                                    type="button"
                                    data-share-pickup-qr
                                    data-download-url="{{ route('packages.pickup-qr.download', $package) }}"
                                    data-filename="qr-{{ $package->tracking_code }}.png"
                                    data-whatsapp-url="{{ $whatsAppPickupShareUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-green-600 px-5 py-3 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-wait disabled:opacity-60"
                                >Compartir por WhatsApp</button>
                            @endcan
                        @endif
                    @endif
                </div>
                @if ($hasShareablePickupQr)
                    @can('sharePickupQr', $package)
                        <p class="mt-2 text-xs text-gray-500">En dispositivos compatibles se compartirá el archivo PNG. Si WhatsApp se abre como respaldo, adjunta manualmente la imagen descargada.</p>
                        <div data-pickup-qr-share-status class="mt-3 hidden rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900" role="status" aria-live="polite"></div>
                    @endcan
                @endif
                @if ($defaultPrinter && ! in_array($package->status, [\App\Enums\PackageStatus::Delivered, \App\Enums\PackageStatus::Cancelled], true))
                    <p class="mt-2 text-xs text-gray-500">Crea un trabajo para {{ $defaultPrinter->name }}. El agente local lo procesará; esta pantalla no imprime directamente.</p>
                @endif
                @can('cancel', $package)
                    <details class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4">
                        <summary class="cursor-pointer text-sm font-bold text-red-800">Anular paquete</summary>
                        <div class="mt-3 text-sm text-red-800">Esta acción impide la entrega, invalida el QR activo y conserva el paquete para auditoría.</div>
                        <form method="POST" action="{{ route('packages.cancel', $package) }}" class="mt-4 space-y-3" onsubmit="return confirm('¿Confirmas que deseas anular este paquete? Esta acción no se puede deshacer.');">
                            @csrf
                            <div>
                                <x-input-label for="reason" value="Motivo de anulación" />
                                <textarea id="reason" name="reason" rows="3" maxlength="1000" class="mt-1.5 block w-full rounded-lg border-red-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500" required>{{ old('reason') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-red-700 px-5 py-3 text-sm font-bold text-white hover:bg-red-800 sm:w-auto">Confirmar anulación</button>
                        </form>
                    </details>
                @endcan
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">Remitente</h2>
                    <dl class="mt-5 space-y-4"><div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->sender_name }}</dd></div><div><dt class="text-sm font-medium text-gray-500">Celular</dt><dd class="mt-1 text-gray-800">{{ $package->sender_phone }}</dd></div>@if ($package->seller)<div><dt class="text-sm font-medium text-gray-500">Vendedor registrado</dt><dd class="mt-1"><a href="{{ route('sellers.show', $package->seller) }}" class="font-semibold text-tik-red-dark hover:text-tik-red">Ver ficha de {{ $package->seller->name }}</a></dd></div>@endif</dl>
                </section>
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">Destinatario</h2>
                    <dl class="mt-5 space-y-4"><div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->recipient_name }}</dd></div><div><dt class="text-sm font-medium text-gray-500">Celular</dt><dd class="mt-1 text-gray-800">{{ $package->recipient_phone }}</dd></div></dl>
                </section>
            </div>

            <section class="grid grid-cols-1 gap-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 lg:grid-cols-2">
                <div><h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Descripción</h2><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-800">{{ $package->description ?: 'Sin descripción' }}</p></div>
                <div><h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Observaciones</h2><p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-800">{{ $package->notes ?: 'Sin observaciones' }}</p></div>
            </section>
        </div>
    </div>
</x-app-layout>
