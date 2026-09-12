@csrf
@if ($seller->exists)
    @method('PUT')
@endif

@if ($returnTo ?? null)
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
@endif

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nombre" />
        <x-text-input id="name" name="name" type="text" maxlength="150" class="mt-1.5 block w-full" :value="old('name', $seller->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="business_name" value="Nombre del negocio" />
        <x-text-input id="business_name" name="business_name" type="text" maxlength="150" class="mt-1.5 block w-full" :value="old('business_name', $seller->business_name)" />
        <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="phone" value="Celular" />
        <x-text-input id="phone" name="phone" type="text" inputmode="tel" maxlength="30" class="mt-1.5 block w-full" :value="old('phone', $seller->phone)" required />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <x-input-label for="document_type" value="Tipo de documento" />
            <select id="document_type" name="document_type" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">
                <option value="">Sin especificar</option>
                @foreach ($documentTypes as $documentType)
                    <option value="{{ $documentType->value }}" @selected(old('document_type', $seller->document_type?->value) === $documentType->value)>{{ $documentType->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="document_number" value="Número" />
            <x-text-input id="document_number" name="document_number" type="text" maxlength="50" class="mt-1.5 block w-full" :value="old('document_number', $seller->document_number)" />
            <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
        </div>
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="address" value="Dirección" />
        <x-text-input id="address" name="address" type="text" maxlength="255" class="mt-1.5 block w-full" :value="old('address', $seller->address)" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="notes" value="Notas" />
        <textarea id="notes" name="notes" rows="4" maxlength="2000" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('notes', $seller->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
    @if (in_array(Auth::user()->role, [\App\Enums\UserRole::Owner, \App\Enums\UserRole::Admin], true))
        <div class="sm:col-span-2">
            <input type="hidden" name="active" value="0">
            <label class="inline-flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3">
                <input name="active" type="checkbox" value="1" @checked((bool) old('active', $seller->active ?? true)) class="rounded border-gray-300 text-tik-red shadow-sm focus:ring-tik-red">
                <span class="text-sm font-semibold text-tik-ink">Vendedor activo</span>
            </label>
        </div>
    @else
        <input type="hidden" name="active" value="1">
    @endif
</div>

<div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ ($returnTo ?? null) === 'packages.create' ? route('packages.create') : route('sellers.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
    <x-primary-button class="justify-center px-5 py-2.5">Guardar vendedor</x-primary-button>
</div>
