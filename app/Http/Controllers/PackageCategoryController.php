<?php

namespace App\Http\Controllers;

use App\Actions\PackageCategories\CreatePackageCategoryAction;
use App\Actions\PackageCategories\UpdatePackageCategoryAction;
use App\Http\Requests\PackageCategories\StorePackageCategoryRequest;
use App\Http\Requests\PackageCategories\UpdatePackageCategoryRequest;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PackageCategoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PackageCategory::class);

        /** @var User $user */
        $user = $request->user();
        $categories = PackageCategory::query()
            ->whereBelongsTo($user->company)
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        return view('package-categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        Gate::authorize('create', PackageCategory::class);

        return view('package-categories.create');
    }

    public function store(StorePackageCategoryRequest $request, CreatePackageCategoryAction $createCategory): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $category = $createCategory->execute($user, $request->validated());

        return redirect()
            ->route('package-categories.edit', $category)
            ->with('status', 'Categoría creada correctamente.');
    }

    public function edit(PackageCategory $packageCategory): View
    {
        Gate::authorize('update', $packageCategory);

        return view('package-categories.edit', ['category' => $packageCategory]);
    }

    public function update(
        UpdatePackageCategoryRequest $request,
        PackageCategory $packageCategory,
        UpdatePackageCategoryAction $updateCategory,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $category = $updateCategory->execute($user, $packageCategory, $request->validated());

        return redirect()
            ->route('package-categories.edit', $category)
            ->with('status', 'Categoría actualizada correctamente.');
    }
}
