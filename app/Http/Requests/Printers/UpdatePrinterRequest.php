<?php

namespace App\Http\Requests\Printers;

use App\Enums\PrinterConnectionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePrinterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('printer')) ?? false;
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
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $this->user()->company_id),
            ],
            'connection_type' => ['required', Rule::enum(PrinterConnectionType::class)],
            'ip_address' => ['required_if:connection_type,lan', 'nullable', 'ip'],
            'port' => ['required_if:connection_type,lan', 'nullable', 'integer', 'min:1', 'max:65535'],
            'paper_width' => ['required', Rule::in([58, 80])],
            'is_default' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $active = $this->has('active') ? $this->boolean('active') : true;

                if ($this->boolean('is_default') && ! $active) {
                    $validator->errors()->add('is_default', 'Una impresora inactiva no puede ser predeterminada.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'name' => $this->string('name')->trim()->toString(),
        ];

        foreach (['is_default', 'active'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = $this->boolean($field);
            }
        }

        $this->merge($normalized);
    }
}
