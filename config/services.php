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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    //untuk google api sheet
    'google_sheets' => [
        'credentials_path' => storage_path('app/google/service-account.json'),
        'spreadsheet_id' => env('GOOGLE_SHEET_ID'),
        'range' => env('GOOGLE_SHEET_RANGE'),
        'app_name' => env('APP_NAME', 'Laravel') . ' Sheet Sync',
        'sync_token' => env('SHEET_SYNC_TOKEN'),
        'jamaah_id' => env('GOOGLE_SHEET_JAMAAH_ID'),
    ],

];
