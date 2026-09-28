<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

abstract class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::forUser($this->user())->allows('viewReports');
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    protected function dateRules(): array
    {
        return [
            'period' => ['required', Rule::enum(ReportPeriod::class)],
            'date_from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $hasCustomDates = $this->filled('date_from') || $this->filled('date_to');

        $this->merge([
            'period' => $hasCustomDates
                ? ReportPeriod::Custom->value
                : ($this->input('period') ?: ReportPeriod::ThisMonth->value),
        ]);
    }
}
