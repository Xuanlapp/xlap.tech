@props([
    'title',
    'accent' => 'text-cyan-300',
    'description',
    'preset',
])

<x-app-layout>
    <section class="min-h-[calc(100vh-4rem)] bg-[#111217] text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $accent }}">Offorest workspace</p>
                    <h1 class="mt-2 text-3xl font-semibold">{{ $title }}</h1>
                </div>
                <span class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm text-white/70">Enabled</span>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-lg font-semibold">Image workflow</h2>
                    <p class="mt-2 text-sm leading-6 text-white/65">{{ $description }}</p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-md bg-white/[0.06] p-4">
                            <p class="text-xs uppercase text-white/45">Input</p>
                            <p class="mt-2 font-medium">Source image</p>
                        </div>
                        <div class="rounded-md bg-white/[0.06] p-4">
                            <p class="text-xs uppercase text-white/45">Prompt</p>
                            <p class="mt-2 font-medium">{{ $preset }}</p>
                        </div>
                        <div class="rounded-md bg-white/[0.06] p-4">
                            <p class="text-xs uppercase text-white/45">Output</p>
                            <p class="mt-2 font-medium">Generated assets</p>
                        </div>
                    </div>
                </div>

                <aside class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-lg font-semibold">Access</h2>
                    <p class="mt-2 text-sm text-white/65">Bạn nhìn thấy page này vì admin đã gán quyền {{ $title }} cho tài khoản.</p>
                </aside>
            </div>
        </div>
    </section>
</x-app-layout>
