<?php

namespace App\Http\Requests\PrinterAgents;

use App\Models\Branch;
use App\Models\PrinterAgent;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrinterAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', PrinterAgent::class) ?? false;
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
                'integer',
                Rule::exists(Branch::class, 'id')->where(function ($query): void {
                    /** @var User $user */
                    $user = $this->user();
                    $query->where('company_id', $user->company_id);
                }),
            ],
        ];
    }
}
