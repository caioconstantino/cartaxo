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

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    ],

    'bling' => [
    'client_id' => env('BLING_CLIENT_ID'),
    'client_secret' => env('BLING_CLIENT_SECRET'),
    'basic_auth' => env('BLING_BASIC_AUTH'),
    'natureza_operacao_id' => env('BLING_NATUREZA_OPERACAO_ID', 1),
    'base_url' => 'https://api.bling.com.br/Api/v3', // para chamadas normais
    'token_url' => 'https://bling.com.br/Api/v3/oauth/token', // para refresh token
],


];
