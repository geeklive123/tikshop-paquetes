<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-tik-red">Vendedores</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Nuevo vendedor</h1>
            <p class="mt-1 text-sm text-gray-500">Registra los datos comerciales y de contacto.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sellers.store') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('sellers._form', ['seller' => new \App\Models\Seller])
            </form>
        </div>
    </div>
</x-app-layout>
