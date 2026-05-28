<button {{ $attributes->merge(["class" => "bg-gray-400 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-400 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150"])}}>
    {{ $slot }}
</button>