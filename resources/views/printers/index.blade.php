<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Configuración</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Impresoras</h1>
                <p class="mt-1 text-sm text-gray-500">Configura impresoras térmicas por sucursal.</p>
            </div>
            @can('create', \App\Models\Printer::class)
                <a href="{{ route('printers.create') }}" class="inline-flex items-center justify-center rounded-xl bg-tik-red px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tik-red-dark">Nueva impresora</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Nombre</th>
                                <th class="px-5 py-3">Sucursal</th>
                                <th class="px-5 py-3">Conexión</th>
                                <th class="px-5 py-3">Papel</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Predeterminada</th>
                                <th class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($printers as $printer)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-tik-ink">{{ $printer->name }}</td>
                                    <td class="px-5 py-4 text-gray-700">{{ $printer->branch->name }}</td>
                                    <td class="px-5 py-4 text-gray-700">
                                        <span class="font-semibold">{{ $printer->connection_type->label() }}</span>
                                        @if ($printer->connection_type === \App\Enums\PrinterConnectionType::Lan)
                                            <span class="block font-mono text-xs text-gray-500">{{ $printer->ip_address }}:{{ $printer->port }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-700">{{ $printer->paper_width }} mm</td>
                                    <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-50 text-green-700' => $printer->active, 'bg-gray-100 text-gray-600' => ! $printer->active])>{{ $printer->active ? 'Activa' : 'Inactiva' }}</span></td>
                                    <td class="px-5 py-4 text-gray-700">{{ $printer->is_default ? 'Sí' : 'No' }}</td>
                                    <td class="px-5 py-4">
                                        @can('update', $printer)
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('printers.edit', $printer) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Editar</a>
                                                <form method="POST" action="{{ route('printers.status.update', $printer) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ $printer->active ? 'Desactivar' : 'Activar' }}</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="block text-right text-xs text-gray-400">Solo lectura</span>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No hay impresoras configuradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $printers->links() }}
        </div>
    </div>
</x-app-layout>
