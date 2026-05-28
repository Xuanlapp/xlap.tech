<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
    <!-- drawer init and show -->
    <div class="text-center bg-blue-300">
        <button class="bg-slate-600 text-white p-3" wire:click="createFolder">Create Folder</button>
        <img src="{{asset('kdrive/basketball_logo_pdf/Bullets_WAS_1987_WB187A1.svg')}}" alt="Unrivaled" class="w-72 mx-auto mt-4 mb-6"/>
        <iframe src="{{asset('kdrive/basketball_logo_pdf/Bullets_WAS_1987_WB187A1.pdf')}}#toolbar=0&navpanes=0&scrollbar=0&view=FitH&zoom=page-fit&transparent=1"
                    width="250px"
                    height="250px"
                    style="border: none; background-color: transparent; overflow: hidden;"
                    allowfullscreen
                    scrolling="no"class="w-72 h-72"></iframe>
    </div>
</div>
