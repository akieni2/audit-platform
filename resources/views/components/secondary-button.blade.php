<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 dark:border-[rgba(0,209,255,0.22)] dark:bg-[#10192B] dark:text-[#E6EEF8] dark:hover:bg-[#122038] dark:hover:border-[rgba(0,209,255,0.35)] dark:focus:ring-[#00D1FF] dark:focus:ring-offset-[#050816]']) }}>
    {{ $slot }}
</button>
