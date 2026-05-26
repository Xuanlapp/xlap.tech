@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.55),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(59,130,246,0.20),transparent_26%),radial-gradient(circle_at_50%_50%,rgba(14,165,233,0.18),transparent_30%)]"></div>
    <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.20),rgba(255,255,255,0.04))]"></div>

    <div class="relative flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="absolute inset-x-0 top-8 mx-auto h-28 w-28 rounded-full bg-white/35 blur-3xl"></div>

        <div class="w-full max-w-md rounded-[2rem] border border-white/40 bg-white/45 p-8 shadow-[0_25px_80px_rgba(15,23,42,0.18)] backdrop-blur-2xl ring-1 ring-white/50 sm:p-10">
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/65 shadow-inner shadow-white/70 ring-1 ring-white/70">
                    <svg viewBox="0 0 64 64" class="h-8 w-8 text-sky-400" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M14 33C19 25 28 20 39 20c4.7 0 8.9 1 12 2.8-2.2 5.8-7 10.2-13 12.1-6.2 1.9-12.8 1.1-19-1.9-2.2-1-4.2-2.3-5-3z" fill="currentColor" opacity="0.35"/>
                        <path d="M15 31.5c3.8-2.6 8.9-4.4 14.8-4.4 8 0 14.9 3 19.6 7.9-4.2 6.2-11.2 10-18.6 10-5.8 0-11.4-2.1-15.8-6-1.8-1.6-3-3.1-4-4.6 1.2-1 2.4-1.9 4-2.9z" fill="currentColor" opacity="0.8"/>
                        <circle cx="47.5" cy="15.5" r="2" fill="currentColor" opacity="0.55"/>
                        <circle cx="54" cy="20" r="1.2" fill="currentColor" opacity="0.45"/>
                        <circle cx="41.5" cy="12.5" r="1.1" fill="currentColor" opacity="0.4"/>
                    </svg>
                </div>

                <h1 class="mt-6 text-2xl font-semibold tracking-[0.18em] text-slate-900">CHÀO MỪNG BẠN</h1>
                <p class="mt-2 text-sm text-slate-600">Vui lòng đăng nhập để tiếp tục.</p>
            </div>

            @if (session('status'))
                <div class="mt-5 text-center text-sm text-green-600">{{ session('status') }}</div>
            @endif

            <form class="mt-6 space-y-4" method="POST" action="{{ url('login') }}">
                @csrf

                <div>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                                <circle cx="12" cy="8" r="4" />
                            </svg>
                        </span>
                        <input
                            id="login"
                            type="text"
                            name="login"
                            value="{{ old('login') }}"
                            autocomplete="username"
                            autofocus
                            placeholder="Email / Tên đăng nhập"
                            class="w-full rounded-full border border-slate-300/80 bg-white/75 py-3.5 pl-12 pr-4 text-sm text-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.65)] outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:ring-4 focus:ring-sky-400/20"
                        />
                    </div>
                    @error('login') <div class="mt-2 px-3 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 1 0-8 0v4" />
                                <rect x="5" y="11" width="14" height="10" rx="2" />
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Mật khẩu"
                            class="w-full rounded-full border border-slate-300/80 bg-white/75 py-3.5 pl-12 pr-12 text-sm text-slate-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.65)] outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:ring-4 focus:ring-sky-400/20"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-4 flex items-center text-slate-400 transition hover:text-slate-600"
                            aria-label="Hiện hoặc ẩn mật khẩu"
                            onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    @error('password') <div class="mt-2 px-3 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="flex items-center justify-between gap-4 pt-1">
                    <label for="remember" class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input
                            id="remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-sky-500 focus:ring-sky-400"
                            name="remember"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <span>Ghi nhớ tôi</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm font-medium text-sky-700 transition hover:text-sky-900" href="{{ route('password.request') }}">
                            Quên mật khẩu?
                        </a>
                    @endif
                </div>

                <button
                    type="submit"
                    class="mt-2 flex w-full items-center justify-center rounded-full bg-gradient-to-r from-sky-600 to-cyan-400 px-5 py-3.5 text-sm font-semibold tracking-wide text-white shadow-lg shadow-sky-500/30 transition duration-200 hover:scale-[1.01] hover:from-sky-500 hover:to-cyan-300 focus:outline-none focus:ring-4 focus:ring-sky-400/30"
                >
                    ĐĂNG NHẬP
                </button>

                <div class="flex items-center gap-4 py-2">
                    <div class="h-px flex-1 bg-slate-300/70"></div>
                    <span class="text-sm text-slate-500">Hoặc</span>
                    <div class="h-px flex-1 bg-slate-300/70"></div>
                </div>

                <div class="flex items-center justify-center gap-4">
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full bg-white/80 shadow-md ring-1 ring-white/70 transition hover:-translate-y-0.5 hover:bg-white" aria-label="Đăng nhập bằng Google">
                        <span class="text-xl font-semibold text-[#EA4335]">G</span>
                    </button>
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full bg-white/80 shadow-md ring-1 ring-white/70 transition hover:-translate-y-0.5 hover:bg-white" aria-label="Đăng nhập bằng Apple">
                        <span class="text-xl font-semibold text-slate-900"></span>
                    </button>
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full bg-white/80 shadow-md ring-1 ring-white/70 transition hover:-translate-y-0.5 hover:bg-white" aria-label="Đăng nhập bằng Facebook">
                        <span class="text-xl font-semibold text-[#1877F2]">f</span>
                    </button>
                </div>

                <p class="pt-2 text-center text-sm text-slate-600">
                    Chưa có tài khoản? <span class="font-semibold text-slate-900">Liên hệ quản trị viên</span>
                </p>
            </form>
        </div>
    </div>
</div>
@endsection
