@csrf
@if ($printer->exists)
    @method('PUT')
@endif

@php
    $selectedConnection = old('connection_type', $printer->connection_type?->value ?? \App\Enums\PrinterConnectionType::Lan->value);
    $selectedBranch = old('branch_id', $printer->branch_id ?? $branches->first()?->id);
@endphp

<div x-data="{ connectionType: {{ Illuminate\Support\Js::from($selectedConnection) }} }" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Nombre" />
        <x-text-input id="name" name="name" type="text" maxlength="150" class="mt-1.5 block w-full" :value="old('name', $printer->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="branch_id" value="Sucursal" />
        <select id="branch_id" name="branch_id" required class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-tik-red focus:ring-tik-red">
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) $selectedBranch === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="connection_type" value="Tipo de conexión" />
        <select id="connection_type" name="connection_type" x-model="connectionType" required class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-tik-red focus:ring-tik-red">
            @foreach ($connectionTypes as $connectionType)
                <option value="{{ $connectionType->value }}">{{ $connectionType->label() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('connection_type')" class="mt-2" />
    </div>

    <div x-show="connectionType === 'lan'" x-cloak>
        <x-input-label for="ip_address" value="Dirección IP" />
        <x-text-input id="ip_address" name="ip_address" type="text" maxlength="45" class="mt-1.5 block w-full font-mono" :value="old('ip_address', $printer->ip_address)" placeholder="192.168.1.100" x-bind:required="connectionType === 'lan'" />
        <x-input-error :messages="$errors->get('ip_address')" class="mt-2" />
    </div>

    <div x-show="connectionType === 'lan'" x-cloak>
        <x-input-label for="port" value="Puerto" />
        <x-text-input id="port" name="port" type="number" min="1" max="65535" class="mt-1.5 block w-full" :value="old('port', $printer->port ?? 9100)" placeholder="9100" x-bind:required="connectionType === 'lan'" />
        <p class="mt-1 text-xs text-gray-500">Valor habitual para impresoras térmicas: 9100.</p>
        <x-input-error :messages="$errors->get('port')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="paper_width" value="Ancho de papel" />
        <select id="paper_width" name="paper_width" required class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-tik-red focus:ring-tik-red">
            @foreach ([58, 80] as $paperWidth)
                <option value="{{ $paperWidth }}" @selected((int) old('paper_width', $printer->paper_width ?? 80) === $paperWidth)>{{ $paperWidth }} mm{{ $paperWidth === 80 ? ' (sugerido)' : '' }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('paper_width')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="notes" value="Notas" />
        <textarea id="notes" name="notes" rows="3" maxlength="2000" class="mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-tik-red focus:ring-tik-red">{{ old('notes', $printer->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="flex flex-col gap-3 sm:col-span-2 sm:flex-row">
        <input type="hidden" name="active" value="0">
        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3">
            <input name="active" type="checkbox" value="1" @checked((bool) old('active', $printer->active ?? true)) class="rounded border-gray-300 text-tik-red shadow-sm focus:ring-tik-red">
            <span class="text-sm font-semibold text-tik-ink">Impresora activa</span>
        </label>
        <input type="hidden" name="is_default" value="0">
        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3">
            <input name="is_default" type="checkbox" value="1" @checked((bool) old('is_default', $printer->is_default ?? false)) class="rounded border-gray-300 text-tik-red shadow-sm focus:ring-tik-red">
            <span class="text-sm font-semibold text-tik-ink">Predeterminada para la sucursal</span>
        </label>
        <x-input-error :messages="$errors->get('is_default')" class="mt-2" />
    </div>
</div>

<div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ route('printers.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
    <x-primary-button class="justify-center px-5 py-2.5">Guardar impresora</x-primary-button>
</div>
