<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Código QR válido</p>
            <h1 class="pt-1 text-2xl font-bold tracking-tight text-tik-ink">PAQUETE ENCONTRADO</h1>
            <p class="pt-1 text-sm text-gray-500">Verifica los datos antes de confirmar la entrega.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-col gap-3 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-mono text-2xl font-bold text-tik-red-dark">{{ $package->tracking_code }}</p>
                    <x-package-status-badge :status="$package->status" />
                </div>

                <dl class="grid grid-cols-1 gap-5 py-6 sm:grid-cols-2">
                    <div class="rounded-xl border-2 border-tik-red bg-red-50 p-4 sm:col-span-2">
                        <dt class="text-xs font-bold uppercase tracking-widest text-tik-red-dark">UBICACIÓN</dt>
                        <dd class="mt-1 font-mono text-3xl font-black text-tik-ink">{{ $package->storage_code ?? 'Sin registrar' }}</dd>
                    </div>
                    @if ($package->category)
                        <div><dt class="text-sm font-medium text-gray-500">Categoría / tamaño</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->category->name }}</dd></div>
                    @endif
                    <div><dt class="text-sm font-medium text-gray-500">Destinatario</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->recipient_name }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Celular</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->recipient_phone }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Remitente</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $package->sender_name }}</dd></div>
                    <div><dt class="text-sm font-medium text-gray-500">Descripción</dt><dd class="mt-1 whitespace-pre-line text-sm text-tik-ink">{{ $package->description ?: 'Sin descripción' }}</dd></div>
                </dl>

                <div class="grid grid-cols-1 gap-3 border-t border-gray-100 pt-6 sm:grid-cols-2">
                    @if ($package->status === \App\Enums\PackageStatus::ReadyForPickup)
                        @can('deliver', $package)
                            <form method="POST" action="{{ route('pickup.deliver') }}">
                                @csrf
                                <input type="hidden" name="token" value="{{ $rawToken }}">
                                <button type="submit" class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-tik-red px-6 text-base font-bold text-white transition hover:bg-tik-red-dark">CONFIRMAR ENTREGA</button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('pickup.scanner') }}" class="inline-flex min-h-14 items-center justify-center rounded-xl border border-gray-300 px-6 text-base font-bold text-gray-700 transition hover:bg-tik-gray">Cancelar / volver a escanear</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
