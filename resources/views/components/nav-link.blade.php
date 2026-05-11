@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-[#00D1FF] px-1 pt-1 text-sm font-semibold leading-5 text-gray-900 focus:border-[#00D1FF] focus:outline-none dark:text-[#E6EEF8] transition duration-150 ease-in-out'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-gray-500 hover:border-slate-300 hover:text-gray-800 focus:border-slate-300 focus:text-gray-800 focus:outline-none dark:text-[#9FB3C8] dark:hover:border-[rgba(0,209,255,0.25)] dark:hover:text-[#E6EEF8] dark:focus:border-[rgba(0,209,255,0.25)] dark:focus:text-[#E6EEF8] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
