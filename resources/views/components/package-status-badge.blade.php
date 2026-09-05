@props(['status'])

<span @class([
    'inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold',
    'bg-red-50 text-tik-red-dark ring-1 ring-inset ring-red-200' => $status === \App\Enums\PackageStatus::Received,
    'bg-orange-50 text-amber-900 ring-1 ring-inset ring-tik-box/50' => $status === \App\Enums\PackageStatus::ReadyForPickup,
    'bg-tik-ink text-white ring-1 ring-inset ring-tik-ink' => $status === \App\Enums\PackageStatus::Delivered,
    'bg-tik-gray text-gray-700 ring-1 ring-inset ring-gray-300' => $status === \App\Enums\PackageStatus::Cancelled,
])>{{ $status->label() }}</span>
