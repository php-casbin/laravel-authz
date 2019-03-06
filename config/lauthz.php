<?php

return [
    /*
     *Default Lauthz driver
     */
    'default' => 'basic',

    'basic' => [
        /*
        * Casbin model setting.
        */
        'model' => [
            // Available Settings: "file", "text"
            'config_type' => 'file',

            'config_file_path' => config_path('lauthz-rbac-model.conf'),

            'config_text' => '',
        ],

        /*
        * Casbin adapter .
        */
        'adapter' => Lauthz\Adapters\DatabaseAdapter::class,

        /*
        * Database setting.
        */
        'database' => [
            // Database connection for following tables.
            'connection' => '',

            // Rule table name.
            'rules_table' => 'rules',
        ],

        'log' => [
            // changes whether Lauthz will log messages to the Logger.
            'enabled' => false,

            // Casbin Logger
            'logger' => Lauthz\Logger::class,
        ],

        'cache' => [
            // changes whether Lauthz will cache the rules.
            'enabled' => false,

            // cache store
            'store' => 'default',

            // cache Key
            'key' => 'rules',

            // ttl \DateTimeInterface|\DateInterval|int|null
            'ttl' => 24 * 60,
        ],
    ],
];
