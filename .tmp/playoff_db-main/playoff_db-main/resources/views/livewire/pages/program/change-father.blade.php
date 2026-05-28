<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    <div>
        <h2 class="text-2xl font-bold mb-5">List No Form_ID</h2>
        <table class="table-auto w-full text-left text-gray-500">
            <thead class="bg-slate-200">
            <tr class="h-16">
                <th>Form</th>
                <th>Insert Name</th>
                <th>Cards</th>
                <th>Sequence</th>
                <th>Foil</th>
                <th>Autos</th>
                <th>...</th>
            </tr>
            </thead>
            <tbody>
            @forelse($nullMainPrograms as $program)
                <tr class="h-12 even:bg-slate-50 hover:bg-slate-100">
                    <td>{{ $program->form }}</td>
                    <td>{{ $program->insert_name }}</td>
                    <td>{{ $program->cards }}</td>
                    <td>{{ $program->seq }}</td>
                    <td>{{ $program->foil }}</td>
                    <td>{{ $program->autos }}</td>
                    <td>
                        @role('admin|auditor')
                        <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='$emit("openModal", "modals.program.detail-program", {{ json_encode(['form' => $program->form,'sub_programs_id'=>$program->id , 'code_programs_id' => $program->programs_id]) }})'>Update Form</button>
                        @endrole
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4">Không có bản ghi nào</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
