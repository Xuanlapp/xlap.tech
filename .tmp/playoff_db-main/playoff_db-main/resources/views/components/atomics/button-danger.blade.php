<button {{ $attributes->merge(["class" => "bg-red-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-700 focus:outline-none focus:border-red-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150"])}}>
    {{ $slot }}
</button>