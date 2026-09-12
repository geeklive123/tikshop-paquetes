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

                <section
                    x-data="{
                        query: '',
                        selected: @js((string) old('seller_id', $preselectedSellerId)),
                        sellers: @js($sellers->map(fn ($seller) => ['id' => (string) $seller->id, 'name' => $seller->name, 'business' => $seller->business_name, 'phone' => $seller->phone])->values()),
                        get filtered() {
                            const search = this.query.toLocaleLowerCase().trim();
                            return search === '' ? this.sellers : this.sellers.filter((seller) => `${seller.name} ${seller.business ?? ''} ${seller.phone}`.toLocaleLowerCase().includes(search));
                        },
                        get chosen() { return this.sellers.find((seller) => seller.id === this.selected); }
                    }"
                    class="rounded-2xl border border-gray-200 border-t-tik-red bg-white p-5 shadow-sm sm:border-t-4 sm:p-6"
                >
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div><p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">VENDEDOR</p><p class="mt-1 text-sm text-gray-500">Selecciona quién deja el paquete.</p></div>
                        <a href="{{ route('sellers.create', ['return_to' => 'packages.create']) }}" class="inline-flex items-center justify-center rounded-xl border border-tik-red px-4 py-2 text-sm font-semibold text-tik-red-dark hover:bg-red-50">+ Nuevo vendedor</a>
                    </div>
                    @if (session('status'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">{{ session('status') }}</div>@endif
                    <input type="hidden" name="seller_id" :value="selected">
                    <label for="seller_search" class="text-sm font-semibold text-tik-ink">Buscar vendedor activo</label>
                    <input id="seller_search" x-model="query" type="search" placeholder="Nombre, negocio o celular" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red" autofocus>
                    <div class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-2">
                        <template x-for="seller in filtered" :key="seller.id">
                            <button type="button" @click="selected = seller.id" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition" :class="selected === seller.id ? 'bg-red-50 ring-1 ring-tik-red' : 'hover:bg-gray-50'">
                                <span class="min-w-0"><span class="block truncate text-sm font-semibold text-tik-ink" x-text="seller.name"></span><span class="block truncate text-xs text-gray-500" x-text="seller.business || 'Sin negocio registrado'"></span></span>
                                <span class="shrink-0 text-xs font-medium text-gray-600" x-text="seller.phone"></span>
                            </button>
                        </template>
                        <p x-show="filtered.length === 0" class="px-3 py-4 text-center text-sm text-gray-500">No hay vendedores que coincidan.</p>
                    </div>
                    <div x-show="chosen" class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-tik-red-dark"><span class="font-semibold">Seleccionado:</span> <span x-text="chosen ? (chosen.business || chosen.name) : ''"></span></div>
                    <x-input-error :messages="$errors->get('seller_id')" class="mt-2" />
                </section>

                <section class="rounded-2xl border border-gray-200 border-t-tik-box bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                    <div class="mb-5"><p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">DATOS DE QUIEN RECOGERÁ</p><p class="mt-1 text-sm text-gray-500">Persona que recogerá el paquete.</p></div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div><x-input-label for="recipient_name" value="Nombre" /><x-text-input id="recipient_name" name="recipient_name" type="text" class="mt-1.5 block w-full" :value="old('recipient_name')" required /><x-input-error :messages="$errors->get('recipient_name')" class="mt-2" /></div>
                        <div><x-input-label for="recipient_phone" value="Celular" /><x-text-input id="recipient_phone" name="recipient_phone" type="text" inputmode="tel" class="mt-1.5 block w-full" :value="old('recipient_phone')" required /><x-input-error :messages="$errors->get('recipient_phone')" class="mt-2" /></div>
                    </div>
                </section>

                <section x-data="{ selected: @js((string) old('package_category_id')), prices: @js($categories->mapWithKeys(fn ($category) => [(string) $category->id => number_format((float) $category->price, 2)])) }" class="rounded-2xl border border-gray-200 border-t-tik-box bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                    <div class="mb-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">TAMAÑO Y ALMACENAJE</p>
                        <p class="mt-1 text-sm text-gray-500">Selecciona la categoría y registra la ubicación física.</p>
                    </div>
                    <fieldset>
                        <legend class="text-sm font-semibold text-tik-ink">Categoría / tamaño</legend>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @forelse ($categories as $category)
                                <label class="relative cursor-pointer overflow-hidden rounded-xl border bg-white p-4 transition hover:border-tik-red" :class="selected == '{{ $category->id }}' ? 'border-tik-red ring-2 ring-red-100' : 'border-gray-200'">
                                    <span class="absolute inset-x-0 top-0 h-1" style="background-color: {{ $category->color ?: '#6B7280' }}"></span>
                                    <input type="radio" name="package_category_id" value="{{ $category->id }}" x-model="selected" class="sr-only" required>
                                    <span class="block font-bold text-tik-ink">{{ $category->name }}</span>
                                    <span class="mt-2 block font-mono text-xs text-gray-500">{{ $category->codeRange() }}</span>
                                    <span class="mt-3 block text-sm font-bold text-tik-red-dark">Bs {{ number_format((float) $category->price, 2) }}</span>
                                </label>
                            @empty
                                <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 sm:col-span-2 lg:col-span-4">No hay categorías activas. Solicita a un administrador que configure una.</p>
                            @endforelse
                        </div>
                        <x-input-error :messages="$errors->get('package_category_id')" class="mt-2" />
                    </fieldset>
                    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:items-end">
                        <div>
                            <x-input-label for="storage_code" value="Código / ubicación de almacenaje" />
                            <x-text-input id="storage_code" name="storage_code" type="text" maxlength="50" class="mt-1.5 block w-full uppercase" :value="old('storage_code')" placeholder="Ej.: S5-11" required />
                            <x-input-error :messages="$errors->get('storage_code')" class="mt-2" />
                        </div>
                        <div class="rounded-xl bg-red-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Costo seleccionado</p>
                            <p class="mt-1 text-xl font-bold text-tik-red-dark" x-text="selected && prices[selected] ? `Bs ${prices[selected]}` : 'Selecciona una categoría'"></p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 border-t-tik-ink bg-white p-5 shadow-sm sm:border-t-4 sm:p-6">
                    <div class="mb-5"><p class="text-xs font-bold uppercase tracking-wider text-tik-red-dark">INFORMACIÓN DEL PAQUETE</p><p class="mt-1 text-sm text-gray-500">Detalles para identificarlo con facilidad.</p></div>
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
