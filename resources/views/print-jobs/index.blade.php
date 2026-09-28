<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Impresión local</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Impresiones</h1>
                <p class="mt-1 text-sm text-gray-500">Seguimiento de trabajos enviados al agente local.</p>
            </div>
            @can('create', \App\Models\PrinterAgent::class)
                <a href="{{ route('printer-agents.create') }}" class="inline-flex items-center justify-center rounded-xl bg-tik-red px-4 py-2.5 text-sm font-semibold text-white hover:bg-tik-red-dark">Nuevo agente</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>
            @endif
            <x-input-error :messages="$errors->get('print_job')" />

            @if ($agents->isNotEmpty())
                <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" aria-label="Agentes locales">
                    @foreach ($agents as $agent)
                        <a href="{{ route('printer-agents.edit', $agent) }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-gray-300">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-bold text-tik-ink">{{ $agent->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $agent->branch->name }}</p>
                                </div>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-green-50 text-green-700' => $agent->isOnline(), 'bg-gray-100 text-gray-600' => ! $agent->isOnline()])>{{ $agent->isOnline() ? 'En línea' : 'Fuera de línea' }}</span>
                            </div>
                            <p class="mt-2 text-xs text-gray-400">{{ $agent->last_seen_at ? 'Visto '.$agent->last_seen_at->diffForHumans() : 'Sin heartbeat' }}</p>
                        </a>
                    @endforeach
                </section>
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Paquete</th><th class="px-5 py-3">Impresora</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Intentos</th><th class="px-5 py-3">Solicitó</th><th class="px-5 py-3">Error</th><th class="px-5 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($jobs as $job)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $job->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 font-semibold text-tik-ink">{{ $job->package?->tracking_code ?? ($job->payload['tracking_code'] ?? '—') }}</td>
                                    <td class="px-5 py-4 text-gray-700">{{ $job->printer->name }}</td>
                                    <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $job->status->label() }}</span></td>
                                    <td class="px-5 py-4 text-gray-700">{{ $job->attempts }}</td>
                                    <td class="px-5 py-4 text-gray-700">{{ $job->requestedBy->name }}</td>
                                    <td class="max-w-xs px-5 py-4 text-xs text-red-700">{{ $job->error_message ?? '—' }}</td>
                                    <td class="px-5 py-4">
                                        @can('update', $job)
                                            <div class="flex justify-end gap-2">
                                                @if ($job->status === \App\Enums\PrintJobStatus::Failed)
                                                    <form method="POST" action="{{ route('print-jobs.retry', $job) }}">@csrf<button class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold hover:bg-gray-50">Reintentar</button></form>
                                                @elseif ($job->status === \App\Enums\PrintJobStatus::Pending)
                                                    <form method="POST" action="{{ route('print-jobs.cancel', $job) }}">@csrf<button class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold hover:bg-gray-50">Cancelar</button></form>
                                                @endif
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">No hay trabajos de impresión.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $jobs->links() }}
        </div>
    </div>
</x-app-layout>
