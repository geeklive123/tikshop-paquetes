@props(['package', 'storageAmount'])

<section class="mb-6 rounded-xl border p-4 {{ $package->payment_status === \App\Enums\PackagePaymentStatus::Paid ? 'border-green-200 bg-green-50' : ($package->payment_status === \App\Enums\PackagePaymentStatus::Pending ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50') }}">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-gray-700">Tiempo almacenado</p>
        <p class="text-xl font-black text-tik-ink">{{ $storageAmount['daysStored'] }} {{ $storageAmount['daysStored'] === 1 ? 'día' : 'días' }}</p>
    </div>

    @if ($package->payment_status === \App\Enums\PackagePaymentStatus::Pending)
        <div class="mt-4 border-t border-red-200 pt-4">
            <p class="font-bold text-red-900">POR PAGAR</p>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-red-700">Precio base</dt><dd class="font-bold text-red-950">Bs {{ $storageAmount['baseAmount'] }}</dd></div>
                <div><dt class="text-red-700">Recargo semanal</dt><dd class="font-bold text-red-950">Bs {{ $storageAmount['surchargeAmount'] }}</dd></div>
            </dl>
            <div class="mt-4 rounded-xl bg-white/70 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-red-700">TOTAL A COBRAR</p>
                <p class="mt-1 text-3xl font-black text-red-900">Bs {{ $storageAmount['totalAmount'] }}</p>
            </div>
            <p class="mt-3 text-sm text-red-800">Este paquete tiene almacenaje pendiente de pago.</p>
            @can('markPaid', $package)
                <form method="POST" action="{{ route('packages.payment.store', $package) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-green-700 px-5 text-sm font-bold text-white hover:bg-green-800 sm:w-auto">MARCAR COMO PAGADO</button>
                </form>
            @endcan
        </div>
    @elseif ($package->payment_status === \App\Enums\PackagePaymentStatus::Paid)
        <div class="mt-4 border-t border-green-200 pt-4">
            <p class="font-bold text-green-900">PAGADO</p>
            <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <div><dt class="text-green-700">Pagado</dt><dd class="font-black text-green-950">Bs {{ $package->paid_amount }}</dd></div>
                <div><dt class="text-green-700">Fecha</dt><dd class="font-semibold text-green-950">{{ $package->paid_at?->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-green-700">Registrado por</dt><dd class="font-semibold text-green-950">{{ $package->paidBy?->name ?? 'Usuario no disponible' }}</dd></div>
            </dl>
        </div>
    @else
        <div class="mt-4 border-t border-gray-200 pt-4">
            <p class="font-bold text-gray-700">SIN SEGUIMIENTO HISTÓRICO</p>
            <p class="mt-1 text-sm text-gray-600">Este registro no genera deuda ni se incluye como pago.</p>
        </div>
    @endif
</section>
