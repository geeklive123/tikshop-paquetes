<?php

namespace App\Http\Requests\Reports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class CancellationReportRequest extends ReportRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...$this->dateRules(),
            'seller_id' => ['nullable', 'integer', Rule::exists('sellers', 'id')->where('company_id', $this->user()->company_id)],
            'cancelled_by' => ['nullable', 'integer', Rule::exists('users', 'id')->where('company_id', $this->user()->company_id)],
        ];
    }
}
