<?php

use App\Modules\Tenant\Models\Tenant;
use Sprout\TenancyOptions;

return [

    'defaults' => [
        'tenancy'  => 'tenants',
        'provider' => 'tenants',
        'resolver' => 'subdomain',
    ],

    'tenancies' => [
        'tenants' => [
            'provider' => 'tenants',
            'options'  => [
                TenancyOptions::hydrateTenantRelation(),
                TenancyOptions::throwIfNotRelated(),
                TenancyOptions::allOverrides(),
            ],
        ],
    ],

    'providers' => [
        'tenants' => [
            'driver' => 'eloquent',
            'model'  => Tenant::class,
        ],
    ],

    'resolvers' => [
        'subdomain' => [
            'driver'  => 'subdomain',
            'domain'  => env('TENANTED_DOMAIN'),
            'pattern' => '.*',
        ],

        'header' => [
            'driver' => 'header',
            'header' => '{Tenancy}-Identifier',
        ],

        'path' => [
            'driver'  => 'path',
            'segment' => 1,
        ],

        'cookie' => [
            'driver' => 'cookie',
            'cookie' => '{Tenancy}-Identifier',
        ],

        'session' => [
            'driver'  => 'session',
            'session' => 'multitenancy.{tenancy}',
        ],
    ],

];
