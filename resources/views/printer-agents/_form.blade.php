@csrf
@if ($printerAgent->exists)
    @method('PUT')
@endif
<div>
    <x-input-label for="name" value="Nombre del agente" />
    <x-text-input id="name" name="name" maxlength="150" class="mt-1.5 block w-full" :value="old('name', $printerAgent->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
<div>
    <x-input-label for="branch_id" value="Sucursal" />
    <select id="branch_id" name="branch_id" required class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-tik-red focus:ring-tik-red">
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $printerAgent->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
</div>
<div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ route('print-jobs.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
    <x-primary-button class="justify-center px-5 py-2.5">Guardar agente</x-primary-button>
</div>
