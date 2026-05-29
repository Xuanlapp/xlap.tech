<?php

return [
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'max:'.env('LIVEWIRE_UPLOAD_MAX_KB', 204800)],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => env('LIVEWIRE_UPLOAD_MAX_MINUTES', 15),
        'cleanup' => true,
    ],
];
