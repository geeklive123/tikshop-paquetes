<?php

namespace App\Http\Requests\Sellers;

use App\Enums\SellerDocumentType;
use App\Models\Seller;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Seller::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'document_type' => ['nullable', Rule::enum(SellerDocumentType::class)],
            'document_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['required', 'boolean'],
            'return_to' => ['nullable', Rule::in(['packages.create'])],
        ];
    }
}
