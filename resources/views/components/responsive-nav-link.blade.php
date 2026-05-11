@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-[#00D1FF] bg-[#122038] py-2 pe-4 ps-3 text-start text-base font-semibold text-[#E6EEF8] focus:bg-[#122038] focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 hover:border-slate-300 hover:bg-gray-50 hover:text-gray-800 focus:border-slate-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none dark:text-[#9FB3C8] dark:hover:border-[rgba(0,209,255,0.2)] dark:hover:bg-[#10192B] dark:hover:text-[#E6EEF8] dark:focus:bg-[#10192B] dark:focus:text-[#E6EEF8] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
