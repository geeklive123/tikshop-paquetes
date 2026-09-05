<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-tik-red">Administración</p>
                <h1 class="pt-1 text-2xl font-bold tracking-tight text-tik-ink">Usuarios</h1>
                <p class="pt-1 text-sm text-gray-500">Gestiona el acceso del equipo de {{ Auth::user()->company->name }}.</p>
            </div>
            <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-tik-red px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-tik-red-dark focus:outline-none focus:ring-2 focus:ring-tik-red focus:ring-offset-2">
                <span aria-hidden="true">+</span> Nuevo usuario
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-tik-red-dark">{{ $errors->first() }}</div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                @if ($users->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <p class="font-semibold text-tik-ink">No hay usuarios disponibles.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 md:hidden">
                        @foreach ($users as $managedUser)
                            <article class="space-y-4 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-tik-ink">{{ $managedUser->name }}</p>
                                        <p class="truncate text-sm text-gray-500">{{ $managedUser->email }}</p>
                                    </div>
                                    <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-tik-ink text-white' => $managedUser->active, 'bg-tik-gray text-gray-600 ring-1 ring-inset ring-gray-300' => ! $managedUser->active])>{{ $managedUser->active ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-tik-box/50">{{ $managedUser->role->label() }}</span>
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('users.edit', $managedUser) }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">Editar</a>
                                        <form method="POST" action="{{ route('users.status.update', $managedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-semibold text-gray-600 hover:text-tik-ink">{{ $managedUser->active ? 'Desactivar' : 'Activar' }}</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-tik-gray">
                                <tr>
                                    @foreach (['Nombre', 'Email', 'Rol', 'Estado', 'Acciones'] as $heading)
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($users as $managedUser)
                                    <tr class="transition hover:bg-red-50/40">
                                        <td class="px-6 py-4 text-sm font-semibold text-tik-ink">{{ $managedUser->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $managedUser->email }}</td>
                                        <td class="px-6 py-4"><span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-tik-box/50">{{ $managedUser->role->label() }}</span></td>
                                        <td class="px-6 py-4"><span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-tik-ink text-white' => $managedUser->active, 'bg-tik-gray text-gray-600 ring-1 ring-inset ring-gray-300' => ! $managedUser->active])>{{ $managedUser->active ? 'Activo' : 'Inactivo' }}</span></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <a href="{{ route('users.edit', $managedUser) }}" class="text-sm font-semibold text-tik-red-dark hover:text-tik-red">Editar</a>
                                                <form method="POST" action="{{ route('users.status.update', $managedUser) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-sm font-semibold text-gray-600 hover:text-tik-ink">{{ $managedUser->active ? 'Desactivar' : 'Activar' }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
