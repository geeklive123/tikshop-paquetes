<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Operaciones</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Paquetes</h1>
                <p class="mt-1 text-sm text-gray-500">Consulta y administra los paquetes registrados.</p>
            </div>
            <a href="{{ route('packages.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-tik-red px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-tik-red-dark focus:outline-none focus:ring-2 focus:ring-tik-red focus:ring-offset-2">
                <span aria-hidden="true">+</span> Nuevo paquete
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('packages.index') }}" class="grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end sm:p-5">
                <div>
                    <x-input-label for="search" value="Buscar paquetes" />
                    <x-text-input id="search" name="search" type="search" class="mt-1.5 block w-full" :value="$search" placeholder="Código, remitente, destinatario o teléfono" />
                </div>
                <div>
                    <x-input-label for="status" value="Estado" />
                    <select id="status" name="status" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-tik-ink px-5 text-sm font-semibold text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-tik-ink focus:ring-offset-2">Buscar</button>
            </form>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                @if ($packages->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <p class="font-semibold text-tik-ink">No se encontraron paquetes.</p>
                        <p class="mt-1 text-sm text-gray-500">Prueba con otros criterios o registra un paquete nuevo.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 md:hidden">
                        @foreach ($packages as $package)
                            <article class="space-y-4 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('packages.show', $package) }}" class="font-mono text-sm font-bold text-tik-red-dark hover:text-tik-red">{{ $package->tracking_code }}</a>
                                    <x-package-status-badge :status="$package->status" />
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div><p class="text-xs font-medium text-gray-500">Remitente</p><p class="mt-1 font-medium text-tik-ink">{{ $package->sender_name }}</p></div>
                                    <div><p class="text-xs font-medium text-gray-500">Destinatario</p><p class="mt-1 font-medium text-tik-ink">{{ $package->recipient_name }}</p><p class="text-gray-500">{{ $package->recipient_phone }}</p></div>
                                </div>
                                <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                                    <time class="text-xs text-gray-500">{{ $package->received_at?->format('d/m/Y H:i') }}</time>
                                    <a href="{{ route('packages.show', $package) }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">Ver</a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-tik-gray">
                                <tr>
                                    @foreach (['Código', 'Remitente', 'Destinatario', 'Teléfono', 'Estado', 'Fecha', 'Acción'] as $heading)
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($packages as $package)
                                    <tr class="transition hover:bg-red-50/50">
                                        <td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-bold text-tik-red-dark">{{ $package->tracking_code }}</td>
                                        <td class="px-5 py-4 text-sm font-medium text-tik-ink">{{ $package->sender_name }}</td>
                                        <td class="px-5 py-4 text-sm font-medium text-tik-ink">{{ $package->recipient_name }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->recipient_phone }}</td>
                                        <td class="whitespace-nowrap px-5 py-4"><x-package-status-badge :status="$package->status" /></td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ $package->received_at?->format('d/m/Y H:i') }}</td>
                                        <td class="whitespace-nowrap px-5 py-4"><a href="{{ route('packages.show', $package) }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">Ver</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{ $packages->links() }}
        </div>
    </div>
</x-app-layout>
