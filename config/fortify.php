<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Fortify will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which password broker Fortify can use when a user
    | is resetting their password. This configured value should match one
    | of your password brokers setup in your "auth" configuration file.
    |
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field.
    |
    */

    'username' => 'email',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    */

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    */

    'home' => '/dashboard',

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Passkeys
    |--------------------------------------------------------------------------
    |
    | Configuration may remain here even though the passkeys feature is
    | currently disabled in the features array below.
    |
    */

    'passkeys' => [
        'relying_party_id' => parse_url(config('app.url'), PHP_URL_HOST),
        'allowed_origins' => [config('app.url')],
        'timeout' => 60000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Initial Eagle Global Hub LTD authentication scope:
    | - Registration
    | - Password reset
    | - Email verification
    | - Profile information updates
    |
    | Two-factor authentication and passkeys are intentionally deferred.
    |
    */

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
    ],

];
