<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nombre" />
        <x-text-input id="name" name="name" type="text" class="mt-1.5 block w-full" :value="old('name', $managedUser?->name)" required autofocus autocomplete="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email', $managedUser?->email)" required autocomplete="email" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="role" value="Rol" />
        <select id="role" name="role" class="mt-1.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-tik-red focus:ring-tik-red" required>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $managedUser?->role?->value) === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>
    <div>
        <input type="hidden" name="active" value="0">
        <label class="flex w-full cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 bg-tik-gray px-4 py-3">
            <span><span class="block text-sm font-semibold text-tik-ink">Usuario activo</span><span class="block text-xs text-gray-500">Puede iniciar sesión y usar el sistema.</span></span>
            <input type="checkbox" name="active" value="1" class="rounded border-gray-300 text-tik-red shadow-sm focus:ring-tik-red" @checked((bool) old('active', $managedUser?->active ?? true))>
        </label>
        <x-input-error :messages="$errors->get('active')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password" :value="$managedUser ? 'Nueva contraseña' : 'Contraseña'" />
        <x-text-input id="password" name="password" type="password" class="mt-1.5 block w-full" :required="! $managedUser" autocomplete="new-password" />
        @if ($managedUser)
            <p class="pt-1.5 text-xs text-gray-500">Déjala vacía para conservar la contraseña actual.</p>
        @endif
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" :required="! $managedUser" autocomplete="new-password" />
    </div>
</div>
