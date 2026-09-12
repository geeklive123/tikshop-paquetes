<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-sm font-semibold text-tik-red">Detalle del vendedor</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">{{ $seller->name }}</h1></div>
            <div class="flex gap-3">@can('update', $seller)<a href="{{ route('sellers.edit', $seller) }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-white">Editar</a>@endcan<a href="{{ route('sellers.index') }}" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver</a></div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>@endif
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <dl class="grid flex-1 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div><dt class="text-sm text-gray-500">Nombre</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $seller->name }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Negocio</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $seller->business_name ?: 'Sin registrar' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Celular</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $seller->phone }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Documento</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $seller->document_type?->label() }} {{ $seller->document_number ?: 'Sin registrar' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Dirección</dt><dd class="mt-1 font-semibold text-tik-ink">{{ $seller->address ?: 'Sin registrar' }}</dd></div>
                    </dl>
                    <div class="flex flex-col items-start gap-3">
                        <span @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-green-50 text-green-700' => $seller->active, 'bg-gray-100 text-gray-600' => ! $seller->active])>{{ $seller->active ? 'Activo' : 'Inactivo' }}</span>
                        @can('toggleStatus', $seller)
                            <form method="POST" action="{{ route('sellers.status.update', $seller) }}">@csrf @method('PATCH')<button class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">{{ $seller->active ? 'Desactivar' : 'Activar' }}</button></form>
                        @endcan
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ([['Total paquetes', $seller->packages_count], ['Pendientes', $seller->pending_packages_count], ['Entregados', $seller->delivered_packages_count]] as [$label, $value])
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-gray-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold text-tik-ink">{{ $value }}</p></div>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-tik-ink">Últimos paquetes</h2></div>
                @if ($recentPackages->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-gray-500">Este vendedor todavía no tiene paquetes.</p>
                @else
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-tik-gray"><tr>@foreach (['Tracking', 'Destinatario', 'Ubicación', 'Estado', 'Fecha'] as $heading)<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-gray-100">@foreach ($recentPackages as $package)<tr><td class="px-5 py-4"><a href="{{ route('packages.show', $package) }}" class="font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</a></td><td class="px-5 py-4 text-sm text-gray-700">{{ $package->recipient_name }}</td><td class="px-5 py-4 font-mono text-sm text-gray-700">{{ $package->storage_code ?: '—' }}</td><td class="px-5 py-4"><x-package-status-badge :status="$package->status" /></td><td class="px-5 py-4 text-sm text-gray-600">{{ $package->received_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>@endforeach</tbody></table></div>
                @endif
            </section>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Total paquetes</p><p class="mt-2 text-3xl font-bold text-tik-ink">{{ $seller->packages_count }}</p></div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm"><p class="text-sm text-amber-800">Pendientes</p><p class="mt-2 text-3xl font-bold text-amber-900">{{ $seller->pending_packages_count }}</p></div>
                <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm"><p class="text-sm text-green-700">Entregados</p><p class="mt-2 text-3xl font-bold text-green-800">{{ $seller->delivered_packages_count }}</p></div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-bold text-tik-ink">Últimos paquetes</h2></div>
                @if ($recentPackages->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-gray-500">Este vendedor todavía no tiene paquetes.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-tik-gray"><tr>@foreach (['Tracking', 'Destinatario', 'Almacenaje', 'Estado', 'Fecha'] as $heading)<th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>@endforeach</tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recentPackages as $package)
                                    <tr class="hover:bg-red-50/40">
                                        <td class="px-5 py-4"><a href="{{ route('packages.show', $package) }}" class="font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</a></td>
                                        <td class="px-5 py-4 text-sm text-tik-ink">{{ $package->recipient_name }}</td>
                                        <td class="px-5 py-4 font-mono text-sm text-gray-600">{{ $package->storage_code ?: '—' }}</td>
                                        <td class="px-5 py-4"><x-package-status-badge :status="$package->status" /></td>
                                        <td class="px-5 py-4 text-sm text-gray-600">{{ $package->received_at?->format('d/m/Y H:i') ?? $package->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
