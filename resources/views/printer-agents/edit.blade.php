<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-tik-red">Impresión local</p><h1 class="mt-1 text-2xl font-bold text-tik-ink">Editar agente</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
        @if (session('status'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>@endif
        @if (session('printer_agent_token'))
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
                <h2 class="font-bold text-amber-900">Guarda este token ahora</h2>
                <p class="mt-1 text-sm text-amber-800">Solo se muestra una vez. Configúralo como <code>PRINT_AGENT_TOKEN</code> en la PC local.</p>
                <code class="mt-3 block break-all rounded-lg bg-white p-3 text-sm text-gray-900">{{ session('printer_agent_token') }}</code>
            </div>
        @endif
        <form method="POST" action="{{ route('printer-agents.update', $printerAgent) }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">@include('printer-agents._form')</form>
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('printer-agents.token', $printerAgent) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">@csrf<h2 class="font-bold text-tik-ink">Token</h2><p class="mt-1 text-sm text-gray-500">Regenerar invalida inmediatamente el token anterior.</p><button class="mt-4 rounded-xl border border-tik-red px-4 py-2.5 text-sm font-semibold text-tik-red-dark">Regenerar token</button></form>
            <form method="POST" action="{{ route('printer-agents.status', $printerAgent) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">@csrf @method('PATCH')<h2 class="font-bold text-tik-ink">Estado</h2><p class="mt-1 text-sm text-gray-500">{{ $printerAgent->active ? 'El agente puede autenticarse.' : 'El agente está bloqueado.' }}</p><button class="mt-4 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold">{{ $printerAgent->active ? 'Desactivar' : 'Activar' }}</button></form>
        </div>
    </div></div>
</x-app-layout>
