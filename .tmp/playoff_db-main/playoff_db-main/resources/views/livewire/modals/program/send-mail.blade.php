<div class="bg-gray-100 py-12">
    <div class="container mx-auto px-6">
        <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-lg">
            <form class="space-y-6" wire:submit.prevent="stylistcontactFormSubmit" action="/appointments" method="POST"
                  enctype="multipart/form-data">
                <span class="text-2xl font-bold text-gray-700 text-center block">
                    Panini PlayOff Contest
                </span>

                @if ($success ?? '')
                    <div class="flex items-center space-x-4">
                        <div class="w-1/4">
                            <img src="{{ asset('images/logos/natural-call.png') }}" class="h-20" alt="">
                        </div>
                        <div class="w-3/4">
                            <div class="alert alert-success bg-green-100 text-green-700 p-4 rounded-md">
                                {{ $success ?? '' }}
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    @error('title')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <input wire:model="title"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           type="text" name="title" value="{{ old('title') }}" placeholder="Enter the title">
                </div>
                <div>
                    @error('name')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <input wire:model="name"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           type="text" name="name" value="{{ old('name') }}" placeholder="NAME">
                </div>
                <div>
                    @error('send_email')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <input wire:model="send_email"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           type="email" name="send_email" placeholder="Enter sender email">
                </div>

                <div>
                    @error('email')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <input wire:model="email"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           type="text" name="email" value="{{ old('email') }}" placeholder="EMAIL">
                </div>


                <div>
                    @error('phone')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <input wire:model="phone"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           type="tel" name="phone" value="{{ old('phone') }}" placeholder="Phone Number">
                </div>

                <div>
                    @error('comment')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <textarea wire:model="comment"
                              class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              name="comment" placeholder="MESSAGE">{{ old('comment') }}</textarea>
                </div>
                <div>
                    @error('file')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <input wire:model="file"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           type="file" name="file" accept="image/*,application/pdf,.xlsx,.csv,.php,.blade.php">
                </div>
                <div class="flex justify-center">
                    <button type="submit"
                            class="px-6 py-3 bg-indigo-500 text-white font-bold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">

                        <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
                        Send Your Message
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
