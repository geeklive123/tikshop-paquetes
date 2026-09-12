<?php

namespace App\Http\Requests\PackageCategories;

use App\Models\PackageCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('package_category')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PackageCategory $packageCategory */
        $packageCategory = $this->route('package_category');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code_prefix' => [
                'required',
                'string',
                'max:10',
                Rule::unique('package_categories')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($packageCategory),
            ],
            'code_start' => ['required', 'integer', 'min:1'],
            'code_end' => ['required', 'integer', 'gte:code_start'],
            'price' => ['required', 'decimal:0,2', 'min:0'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code_prefix' => $this->string('code_prefix')->trim()->upper()->toString(),
        ]);
    }
}
