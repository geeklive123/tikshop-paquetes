@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\PackagePaymentStatus::Paid => 'bg-green-100 text-green-800 ring-green-200',
        \App\Enums\PackagePaymentStatus::Pending => 'bg-red-100 text-red-800 ring-red-200',
        \App\Enums\PackagePaymentStatus::Legacy => 'bg-gray-100 text-gray-700 ring-gray-200',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset', $classes]) }}>
    {{ $status->label() }}
</span>
