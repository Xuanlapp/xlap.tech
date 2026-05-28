<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Mail Notification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans leading-relaxed">
<div class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden mt-8">
    <div class="bg-indigo-600 text-white text-center p-6">
        <h1 class="text-2xl font-semibold">You have a new mail!</h1>
    </div>
    <div class="p-6">
        <div class="mb-4">
            <p class="text-gray-600 mt-2"><strong>Name:</strong> {{ $name }}</p>
            <p class="text-gray-600"><strong>Email:</strong> {{ $email }}</p>
            <p class="text-gray-600"><strong>Phone:</strong> {{ $phone }}</p>
        </div>
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Message</h2>
            <p class="text-gray-600 mt-2">{{ $comment }}</p>
        </div>
    </div>
    <div class="bg-gray-200 text-center p-4">
        <p class="text-sm text-gray-600">&copy; 2024 Your Company. All rights reserved.</p>
    </div>
</div>
</body>
</html>
