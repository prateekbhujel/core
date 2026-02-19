<?php

/**
 * HAARRAY CORE Configuration
 * Shared config for all Haarray apps (HariLog, future apps)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | App Branding
    |--------------------------------------------------------------------------
    */
    'brand_name'    => env('HAARRAY_BRAND', 'Haarray'),
    'app_initial'   => env('HAARRAY_INITIAL', 'H'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'show_telegram_status' => env('HAARRAY_SHOW_TG', false),
    'enable_pwa'           => env('HAARRAY_PWA', true),
    'enable_ml'            => env('HAARRAY_ML', true),

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'token'       => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_BOT_WEBHOOK_URL', env('APP_URL') . '/api/telegram/webhook'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'HariLogBot'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Market Data (all free sources)
    |--------------------------------------------------------------------------
    */
    'market' => [
        'gold_url'      => 'https://www.goldpricenepal.com',
        'nepse_url'     => 'https://merolagani.com/StockQuote.aspx',
        'ipo_url'       => 'https://cdsc.com.np/cdscportal/IpoList.aspx',
        'forex_url'     => 'https://open.er-api.com/v6/latest/USD',
        'nrb_url'       => 'https://www.nrb.org.np/exportForexJSON.php',
        'cache_minutes' => env('MARKET_CACHE_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | IPO Settings
    |--------------------------------------------------------------------------
    */
    'ipo' => [
        'alert_days_before' => 3,
        'min_application'   => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | ML / Suggestion Engine
    |--------------------------------------------------------------------------
    */
    'ml' => [
        'idle_cash_threshold'    => env('HAARRAY_ML_IDLE_CASH_THRESHOLD', 5000),  // NPR — trigger suggestion if idle cash > this
        'food_budget_warning'    => env('HAARRAY_ML_FOOD_BUDGET_WARNING', 0.35),  // Warn if food > 35% of expenses
        'savings_rate_target'    => env('HAARRAY_ML_SAVINGS_RATE_TARGET', 0.30),  // 30% savings rate target
        'retrain_every_days'     => env('HAARRAY_ML_RETRAIN_DAYS', 7),             // Retrain ML model every 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | App Routes (for sidebar active state)
    |--------------------------------------------------------------------------
    */
    'nav_routes' => [
        'dashboard'         => '/',
        'transactions'      => '/transactions',
        'accounts'          => '/accounts',
        'portfolio'         => '/portfolio',
        'ipo'               => '/ipo',
        'market'            => '/market',
        'suggestions'       => '/suggestions',
        'telegram'          => '/telegram',
        'reports'           => '/reports',
        'settings'          => '/settings',
        'profile'           => '/profile',
    ],

];
