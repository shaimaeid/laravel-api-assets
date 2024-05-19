<?php

return [
    'facebook' => [
        'base_url' => (string) env('FACEBOOK_BASE_URL', 'https://graph.facebook.com/v17.0/'),
        'token'    => env('FACEBOOK_TOKEN')
    ],

    'app_url'               => (string) env('APP_URL'),
    'app_env'               => (string) env('APP_ENV'),

    'db_backup_folder'  => 'backups',

    'dst' => [
        'is_active'   => false,
        'start_month' => '04',
        'start_day'   => '28',
        'end_month'   => '10',
        'end_day'     => '31'
    ],


    'creative_website_url' => "https://fb.me",

    'eg' => [
        'id'                       => 1,
        'fx_rate'                  => 1,
        'lm_default_minimum_daily' => 900,
        'min_campaign_days'        => 2,
    ],

    'ae' => [
        'id'                       => 2,
        'fx_rate'                  => 0.12,   // 19 Feb 2024
        'lm_default_minimum_daily' => 900,
        'min_campaign_days'        => 2,
    ],

    "min_fb_budget" => 900,
];
