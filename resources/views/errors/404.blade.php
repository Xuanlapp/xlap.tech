<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>404 - Not Found</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#111217] text-white">
        <main class="flex min-h-screen items-center justify-center px-6">
            <section class="max-w-md text-center">
                <p class="text-sm font-semibold text-cyan-300">404</p>
                <h1 class="mt-3 text-3xl font-bold">Không tìm thấy trang</h1>
                <p class="mt-3 text-sm text-white/60">Đường dẫn này không tồn tại hoặc đã được đổi tên.</p>
                <a href="{{ url('/') }}" class="mt-6 inline-flex rounded-md bg-cyan-500 px-4 py-2 text-sm font-semibold text-white">Về trang chính</a>
            </section>
        </main>
    </body>
</html>
