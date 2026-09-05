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
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif

            <section class="rounded-2xl border border-gray-200 border-l-4 border-l-tik-red bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Código de seguimiento</p><p class="mt-2 font-mono text-2xl font-bold text-tik-red-dark sm:text-3xl">{{ $package->tracking_code }}</p></div>
                    <x-package-status-badge :status="$package->status" />
                </div>
                <dl class="mt-6 grid grid-cols-1 gap-5 border-t border-gray-100 pt-5 sm:grid-cols-3">
                    <div><dt class="text-sm font-medium text-gray-500">Fecha de recepción</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->received_at?->format('d/m/Y H:i') ?? 'Sin registrar' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Recibido por</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->receivedBy?->name ?? 'Sin registrar' }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Sucursal</dt><dd class="mt-1 text-sm font-semibold text-tik-ink">{{ $package->branch->name }}</dd></div>
                </dl>

                @if (! in_array($package->status, [\App\Enums\PackageStatus::Delivered, \App\Enums\PackageStatus::Cancelled], true))
                    @can('generatePickupToken', $package)
                        <form method="POST" action="{{ route('packages.regenerate-qr', $package) }}" class="mt-6 border-t border-gray-100 pt-5">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-tik-red px-5 py-3 text-sm font-semibold text-white transition hover:bg-tik-red-dark sm:w-auto">Regenerar QR</button>
                            <p class="mt-2 text-xs text-gray-500">El código anterior dejará de funcionar inmediatamente.</p>
                        </form>
                    @endcan
                @endif
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">Remitente</h2>
                    <dl class="mt-5 space-y-4"><div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->sender_name }}</dd></div><div><dt class="text-sm font-medium text-gray-500">Celular</dt><dd class="mt-1 text-gray-800">{{ $package->sender_phone }}</dd></div></dl>
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
