<?php

namespace App\Http\Requests\Reports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SellerReportRequest extends ReportRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...$this->dateRules(),
            'sort' => ['nullable', Rule::in(['name', 'packages', 'amount'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
