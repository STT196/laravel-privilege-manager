<?php

/**
 * Laravel Privilege Manager Configuration
 * 
 * This configuration file controls how the privilege system works
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    | Configure the table names used by the privilege system.
    | Fresh Laravel projects can use the defaults; legacy projects can point
    | these values at tbl_* tables.
    |
    */
    'tables' => [
        'user_privileges' => 'tbl_user_privilege',
        'menus' => 'tbl_menu_list',
    ],

    'database' => [
        'users_table' => env('PRIVILEGE_USERS_TABLE', 'users'),
        'users_primary_key' => env('PRIVILEGE_USERS_PRIMARY_KEY', 'id'),
        'menus_table' => env('PRIVILEGE_MENUS_TABLE', 'tbl_menu_list'),
        'privileges_table' => env('PRIVILEGE_PRIVILEGES_TABLE', 'tbl_user_privilege'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Control how privilege data is cached for performance
    |
    */
    'cache' => [
        'enabled' => env('PRIVILEGE_CACHE_ENABLED', true),
        'ttl' => env('PRIVILEGE_CACHE_TTL', 3600), // 1 hour in seconds
        'prefix' => 'privilege_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Prevent abuse of privilege checking endpoints
    |
    */
    'rate_limit' => [
        'enabled' => env('PRIVILEGE_RATE_LIMIT_ENABLED', true),
        'attempts' => env('PRIVILEGE_RATE_LIMIT_ATTEMPTS', 1000),
        'decay_minutes' => env('PRIVILEGE_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Control what privilege operations are logged
    |
    */
    'logging' => [
        'log_checks' => env('PRIVILEGE_LOG_CHECKS', true),
        'log_denials' => env('PRIVILEGE_LOG_DENIALS', true),
        'log_cache_operations' => env('PRIVILEGE_LOG_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    | Specify custom models if you extend the default ones
    |
    */
    'models' => [
        'user' => env('PRIVILEGE_USER_MODEL', 'App\\Models\\User'),
        'user_privilege' => 'LaravelPrivilegeManager\\Models\\UserPrivilege',
        'menu' => 'LaravelPrivilegeManager\\Models\\Menu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    | Additional security configurations
    |
    */
    'security' => [
        'enable_ip_check' => env('PRIVILEGE_CHECK_IP', false),
        'enable_signature_validation' => env('PRIVILEGE_SIGNATURE_VALIDATION', false),
        'allowed_actions' => ['add', 'edit', 'statuschange', 'remove'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Tuning
    |--------------------------------------------------------------------------
    | Optimize for your specific use case
    |
    */
    'performance' => [
        // Use query batching for multiple privilege checks
        'enable_batch_operations' => env('PRIVILEGE_BATCH_OPS', true),
        
        // Preload user privileges on authentication
        'preload_privileges' => env('PRIVILEGE_PRELOAD', true),
        
        // Number of privileges to load in a single query
        'batch_size' => env('PRIVILEGE_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fresh Project Installation
    |--------------------------------------------------------------------------
    | When enabled, the install command will publish package migrations so
    | fresh Laravel projects can run `php artisan migrate` immediately.
    |
    */
    'install' => [
        'publish_migrations' => env('PRIVILEGE_PUBLISH_MIGRATIONS', true),
    ],
];
