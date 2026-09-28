<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Paquetes</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-tik-ink">Editar {{ $package->tracking_code }}</h1>
                <p class="mt-1 text-sm text-gray-500">El tracking, la empresa, la sucursal y las fechas de control no se pueden modificar.</p>
            </div>
            <a href="{{ route('packages.show', $package) }}" class="text-sm font-semibold text-gray-600 hover:text-tik-red-dark">Volver al paquete</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('packages.update', $package) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-input-error :messages="$errors->get('package')" />

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">Vendedor y destinatario</h2>
                    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="seller_id" value="Vendedor" />
                            <select id="seller_id" name="seller_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red" required>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}" @selected((int) old('seller_id', $package->seller_id) === $seller->id)>
                                        {{ $seller->business_name ?: $seller->name }} · {{ $seller->phone }}{{ $seller->active ? '' : ' (inactivo, actual)' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">Al cambiar de vendedor se actualizan el nombre y teléfono del remitente guardados en el paquete.</p>
                            <x-input-error :messages="$errors->get('seller_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="recipient_name" value="Nombre del destinatario" />
                            <x-text-input id="recipient_name" name="recipient_name" type="text" maxlength="150" class="mt-1.5 block w-full" :value="old('recipient_name', $package->recipient_name)" required />
                            <x-input-error :messages="$errors->get('recipient_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="recipient_phone" value="Celular del destinatario" />
                            <x-text-input id="recipient_phone" name="recipient_phone" type="text" inputmode="tel" maxlength="30" class="mt-1.5 block w-full" :value="old('recipient_phone', $package->recipient_phone)" required />
                            <x-input-error :messages="$errors->get('recipient_phone')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">Almacenaje</h2>
                    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="package_category_id" value="Categoría / tamaño" />
                            <select id="package_category_id" name="package_category_id" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('package_category_id', $package->package_category_id) === $category->id)>
                                        {{ $category->name }} · Bs {{ number_format((float) $category->price, 2) }}{{ $category->active ? '' : ' (inactiva, actual)' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">Si cambia la categoría, el monto de almacenaje se actualiza con su precio vigente.</p>
                            <x-input-error :messages="$errors->get('package_category_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="storage_code" value="Código / ubicación" />
                            <x-text-input id="storage_code" name="storage_code" type="text" maxlength="50" class="mt-1.5 block w-full uppercase" :value="old('storage_code', $package->storage_code)" required />
                            <x-input-error :messages="$errors->get('storage_code')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">Información del paquete</h2>
                    <div class="mt-5 space-y-5">
                        <div>
                            <x-input-label for="description" value="Descripción" />
                            <textarea id="description" name="description" rows="3" maxlength="1000" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('description', $package->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Observaciones" />
                            <textarea id="notes" name="notes" rows="4" maxlength="2000" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('notes', $package->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('packages.show', $package) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <x-primary-button class="justify-center px-5 py-2.5">Guardar cambios</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
