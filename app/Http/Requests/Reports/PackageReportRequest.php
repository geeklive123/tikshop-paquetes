<?php

namespace App\Http\Requests\Reports;

use App\Enums\PackageStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class PackageReportRequest extends ReportRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...$this->dateRules(),
            'status' => ['nullable', Rule::enum(PackageStatus::class)],
            'seller_id' => ['nullable', 'integer', Rule::exists('sellers', 'id')->where('company_id', $this->user()->company_id)],
            'category_id' => ['nullable', 'integer', Rule::exists('package_categories', 'id')->where('company_id', $this->user()->company_id)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $this->user()->company_id)],
            'tracking_code' => ['nullable', 'string', 'max:150'],
            'recipient' => ['nullable', 'string', 'max:150'],
        ];
    }
}
