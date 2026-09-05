<?php

namespace App\Http\Requests\Pickup;

use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ManualPackageSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Package::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tracking_code' => ['required', 'string', 'max:15'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tracking_code' => $this->string('tracking_code')->trim()->upper()->toString(),
        ]);
    }
}
