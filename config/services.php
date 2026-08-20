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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'shopee' => [
        'live_push_partner_key' => env('SHOPEE_LIVE_PUSH_PARTNER_KEY'),

        // Kalau order sudah SHIPPED lebih lama dari ini (hari) tapi Shopee
        // belum juga menandainya COMPLETED, status produksinya dipaksa
        // pindah ke "finish" secara lokal supaya tidak menumpuk di dashboard.
        'shipped_grace_days' => env('SHOPEE_SHIPPED_GRACE_DAYS', 14),
    ],

    'absensi' => [
        // Ketiga subdomain di bawah semuanya aktif dan masing-masing memegang
        // sebagian member, jadi selalu ditembak. ABSENSI_API_URLS hanya untuk
        // menambah sumber baru, bukan menggantikan daftar ini, supaya .env yang
        // sudah usang tidak membuat satu sumber diam-diam terlewat.
        'api_urls' => array_values(array_unique(array_filter(array_map('trim', array_merge([
            'https://absen.gudanglanyard.com/api/absensi',
            'https://absensi.gudanglanyard.com/api/absensi',
            'https://absens.gudanglanyard.com/api/absensi',
        ], explode(',', (string) env('ABSENSI_API_URLS', ''))))))),
    ],

];
