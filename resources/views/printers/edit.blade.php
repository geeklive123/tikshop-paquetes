<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Configuración</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Editar impresora</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('printers.update', $printer) }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('printers._form')
            </form>
            <form method="POST" action="{{ route('printers.test-connection', $printer) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-bold text-tik-ink">Prueba de conexión</h2>
                        <p class="mt-1 text-sm text-gray-500">Valida la configuración. La conectividad real se habilitará con el agente local de impresión.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-tik-red px-4 py-2.5 text-sm font-semibold text-tik-red-dark hover:bg-red-50">Probar conexión</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
