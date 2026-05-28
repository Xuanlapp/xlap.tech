<select
        {{ $attributes->merge([
            "class" => "form-select select2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-96 p-2.5"
        ]) }}>
    {{ $slot }}
</select>
@push('scripts')
    <script>
        $(document).ready(function () {
            window.initSelectTeamDrop = () => {
                $('.select2').select2({
                    placeholder: 'Select Team',
                    allowClear: true,
                    //dropdownParent: $('.select2')
                });
            }
            // initSelectTeamDrop();
            $('.select2').on('change', function (e) {
                let value = e.target.value;
                Livewire.emit('selectedCustomerItem', value);
            });
            window.livewire.on('select2', () => {
                initSelectTeamDrop();
            });
            $('.select2').select2();
        });
    </script>
@endpush