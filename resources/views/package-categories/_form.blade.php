@csrf
@if ($category->exists)
    @method('PUT')
@endif

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Nombre" />
        <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $category->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="code_prefix" value="Prefijo del código" />
        <x-text-input id="code_prefix" name="code_prefix" type="text" maxlength="10" class="mt-1.5 block w-full uppercase" :value="old('code_prefix', $category->code_prefix)" required />
        <x-input-error :messages="$errors->get('code_prefix')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="price" value="Precio (Bs)" />
        <x-text-input id="price" name="price" type="number" min="0" step="0.01" class="mt-1.5 block w-full" :value="old('price', $category->price)" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="code_start" value="Inicio del rango" />
        <x-text-input id="code_start" name="code_start" type="number" min="1" class="mt-1.5 block w-full" :value="old('code_start', $category->code_start)" required />
        <x-input-error :messages="$errors->get('code_start')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="code_end" value="Fin del rango" />
        <x-text-input id="code_end" name="code_end" type="number" min="1" class="mt-1.5 block w-full" :value="old('code_end', $category->code_end)" required />
        <x-input-error :messages="$errors->get('code_end')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="color" value="Color" />
        <div class="mt-1.5 flex items-center gap-3">
            <input id="color" name="color" type="color" value="{{ old('color', $category->color ?: '#E5252A') }}" class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1">
            <span class="text-sm text-gray-500">Identificación visual</span>
        </div>
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>
    <div class="flex items-center">
        <input type="hidden" name="active" value="0">
        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3">
            <input name="active" type="checkbox" value="1" @checked((bool) old('active', $category->active ?? true)) class="rounded border-gray-300 text-tik-red shadow-sm focus:ring-tik-red">
            <span class="text-sm font-semibold text-tik-ink">Categoría activa</span>
        </label>
    </div>
</div>

<div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ route('package-categories.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
    <x-primary-button class="justify-center px-5 py-2.5">Guardar categoría</x-primary-button>
</div>
