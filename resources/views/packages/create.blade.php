<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Recepción</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Nuevo paquete</h1>
                <p class="mt-1 text-sm text-gray-500">Registra los datos de entrega y recojo.</p>
            </div>
            <a href="{{ route('packages.index') }}" class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('packages.store') }}" class="space-y-6">
                @csrf

                <section class="rounded-2xl border border-gray-200 border-t-tik-red bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                    <div class="mb-5"><p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">DATOS DE QUIEN ENTREGA</p><p class="mt-1 text-sm text-gray-500">Persona que deja el paquete en la sucursal.</p></div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div><x-input-label for="sender_name" value="Nombre" /><x-text-input id="sender_name" name="sender_name" type="text" class="mt-1.5 block w-full" :value="old('sender_name')" required autofocus /><x-input-error :messages="$errors->get('sender_name')" class="mt-2" /></div>
                        <div><x-input-label for="sender_phone" value="Celular" /><x-text-input id="sender_phone" name="sender_phone" type="text" inputmode="tel" class="mt-1.5 block w-full" :value="old('sender_phone')" required /><x-input-error :messages="$errors->get('sender_phone')" class="mt-2" /></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 border-t-tik-box bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                    <div class="mb-5"><p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">DATOS DE QUIEN RECOGERÁ</p><p class="mt-1 text-sm text-gray-500">Persona que recogerá el paquete.</p></div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div><x-input-label for="recipient_name" value="Nombre" /><x-text-input id="recipient_name" name="recipient_name" type="text" class="mt-1.5 block w-full" :value="old('recipient_name')" required /><x-input-error :messages="$errors->get('recipient_name')" class="mt-2" /></div>
                        <div><x-input-label for="recipient_phone" value="Celular" /><x-text-input id="recipient_phone" name="recipient_phone" type="text" inputmode="tel" class="mt-1.5 block w-full" :value="old('recipient_phone')" required /><x-input-error :messages="$errors->get('recipient_phone')" class="mt-2" /></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 border-t-tik-ink bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                    <div class="mb-5"><p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">INFORMACIÓN DEL PAQUETE</p><p class="mt-1 text-sm text-gray-500">Detalles opcionales para identificarlo con facilidad.</p></div>
                    <div class="space-y-5">
                        <div><x-input-label for="description" value="Descripción" /><textarea id="description" name="description" rows="3" maxlength="1000" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('description') }}</textarea><x-input-error :messages="$errors->get('description')" class="mt-2" /></div>
                        <div><x-input-label for="notes" value="Observaciones" /><textarea id="notes" name="notes" rows="4" maxlength="2000" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('notes') }}</textarea><x-input-error :messages="$errors->get('notes')" class="mt-2" /></div>
                    </div>
                </section>

                <div class="flex justify-end"><x-primary-button class="w-full justify-center px-6 py-3 sm:w-auto">REGISTRAR PAQUETE</x-primary-button></div>
            </form>
        </div>
    </div>
</x-app-layout>
