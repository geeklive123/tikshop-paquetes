<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-tik-red">Impresión local</p><h1 class="mt-1 text-2xl font-bold text-tik-ink">Nuevo agente</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('printer-agents.store') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @include('printer-agents._form', ['printerAgent' => new \App\Models\PrinterAgent])
        </form>
    </div></div>
</x-app-layout>
