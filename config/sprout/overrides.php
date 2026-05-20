<?php

use Sprout\Overrides\AuthGuardOverride;
use Sprout\Overrides\AuthPasswordOverride;
use Sprout\Overrides\CacheOverride;
use Sprout\Overrides\CookieOverride;
use Sprout\Overrides\FilesystemManagerOverride;
use Sprout\Overrides\FilesystemOverride;
use Sprout\Overrides\JobOverride;
use Sprout\Overrides\SessionOverride;
use Sprout\Overrides\StackedOverride;

return [

    'filesystem' => [
        'driver'    => StackedOverride::class,
        'overrides' => [
            FilesystemManagerOverride::class,
            FilesystemOverride::class,
        ],
    ],

    'job' => [
        'driver' => JobOverride::class,
    ],

    'cache' => [
        'driver' => CacheOverride::class,
    ],

    'auth' => [
        'driver'    => StackedOverride::class,
        'overrides' => [
            AuthGuardOverride::class,
            AuthPasswordOverride::class,
        ],
    ],

    'cookie' => [
        'driver' => CookieOverride::class,
    ],

    'session' => [
        'driver'   => SessionOverride::class,
        'database' => false,
    ],

];
