<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'vertex' => [
        'model' => env('VERTEX_MODEL', 'gemini-2.5-flash-image'),
        'text_model' => env('VERTEX_TEXT_MODEL', 'gemini-2.5-flash'),
        'lock_seconds' => env('VERTEX_LOCK_SECONDS', 600),
        'lock_wait_seconds' => env('VERTEX_LOCK_WAIT_SECONDS', 600),
        'priority_lock_wait_seconds' => env('VERTEX_PRIORITY_LOCK_WAIT_SECONDS', 600),
        'cooldown_seconds' => env('VERTEX_COOLDOWN_SECONDS', 90),
        'http_proxy' => env('VERTEX_HTTP_PROXY'),
        'max_input_dimension' => env('VERTEX_MAX_INPUT_DIMENSION', 1400),
        'max_inline_image_bytes' => env('VERTEX_MAX_INLINE_IMAGE_BYTES', 4_194_304),
        'google_drive_thumbnail_size' => env('VERTEX_GOOGLE_DRIVE_THUMBNAIL_SIZE', 1200),
        'debug_payload' => env('VERTEX_DEBUG_PAYLOAD', false),
    ],

    'background_removal' => [
        'enabled' => env('OFFOREST_REMOVE_VERTEX_BACKGROUND', false),
        'engine' => env('OFFOREST_BACKGROUND_REMOVAL_ENGINE', 'magic_eraser'),
        'model' => env('OFFOREST_BACKGROUND_REMOVAL_MODEL', 'briaai/RMBG-1.4'),
        'image_driver' => env('OFFOREST_BACKGROUND_REMOVAL_IMAGE_DRIVER', 'GD'),
        'clean_alpha' => env('OFFOREST_BACKGROUND_REMOVAL_CLEAN_ALPHA', true),
        'alpha_min_opacity' => env('OFFOREST_BACKGROUND_REMOVAL_ALPHA_MIN_OPACITY', 45),
        'min_component_area' => env('OFFOREST_BACKGROUND_REMOVAL_MIN_COMPONENT_AREA', 180),
        'edge_margin_ratio' => env('OFFOREST_BACKGROUND_REMOVAL_EDGE_MARGIN_RATIO', 0.015),
        'foreground_gap_ratio' => env('OFFOREST_BACKGROUND_REMOVAL_FOREGROUND_GAP_RATIO', 0.08),
        'edge_flood_clean' => env('OFFOREST_BACKGROUND_REMOVAL_EDGE_FLOOD_CLEAN', true),
        'edge_color_tolerance' => env('OFFOREST_BACKGROUND_REMOVAL_EDGE_COLOR_TOLERANCE', 58),
        'edge_flood_min_opacity' => env('OFFOREST_BACKGROUND_REMOVAL_EDGE_FLOOD_MIN_OPACITY', 12),
        'edge_color_samples' => env('OFFOREST_BACKGROUND_REMOVAL_EDGE_COLOR_SAMPLES', 3),
        'edge_color_bucket_size' => env('OFFOREST_BACKGROUND_REMOVAL_EDGE_COLOR_BUCKET_SIZE', 24),
    ],

    'psd_mockup_renderer' => [
        'command' => env('PSD_MOCKUP_RENDERER_COMMAND', 'node scripts/psd-renderer/render.js'),
    ],

    'google_drive' => [
        'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON'),
        'service_account_path' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_PATH'),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
        'make_public' => env('GOOGLE_DRIVE_MAKE_PUBLIC', true),
        'supports_all_drives' => env('GOOGLE_DRIVE_SUPPORTS_ALL_DRIVES', true),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI'),
        'scopes' => env('GOOGLE_DRIVE_SCOPES', 'https://www.googleapis.com/auth/drive.file'),
    ],

    'marketplace_listing' => [
        'batch_size' => env('MARKETPLACE_LISTING_BATCH_SIZE', 0),
        'delay_seconds' => env('MARKETPLACE_LISTING_DELAY_SECONDS', 30),
        'lock_seconds' => env('MARKETPLACE_LISTING_LOCK_SECONDS', 21600),
    ],

];
