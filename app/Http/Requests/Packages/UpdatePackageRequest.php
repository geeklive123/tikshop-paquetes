<?php

namespace App\Http\Requests\Packages;

use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $package = $this->route('package');

        if (! $package instanceof Package || $this->user() === null) {
            return false;
        }

        Gate::forUser($this->user())->authorize('update', $package);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Package $package */
        $package = $this->route('package');

        return [
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('sellers', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where(function ($query) use ($package): void {
                        $query->where('active', true)
                            ->orWhere('id', $package->seller_id);
                    })),
            ],
            'package_category_id' => [
                'required',
                'integer',
                Rule::exists('package_categories', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where(function ($query) use ($package): void {
                        $query->where('active', true)
                            ->orWhere('id', $package->package_category_id);
                    })),
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
