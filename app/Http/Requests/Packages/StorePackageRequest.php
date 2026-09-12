<?php

namespace App\Http\Requests\Packages;

use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Package::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('sellers', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('active', true)),
            ],
            'package_category_id' => [
                'required',
                'integer',
                Rule::exists('package_categories', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('active', true)),
            ],
            'storage_code' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_phone' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'storage_code' => $this->string('storage_code')->trim()->upper()->toString(),
        ]);
    }
}
