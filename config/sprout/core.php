<?php

use Sprout\Listeners\CleanupServiceOverrides;
use Sprout\Listeners\PerformIdentityResolverSetup;
use Sprout\Listeners\RefreshTenantAwareDependencies;
use Sprout\Listeners\SetCurrentTenantContext;
use Sprout\Listeners\SetupServiceOverrides;
use Sprout\Support\ResolutionHook;

return [

    'hooks' => [
        ResolutionHook::Routing,
        ResolutionHook::Middleware,
    ],

    'bootstrappers' => [
        SetCurrentTenantContext::class,
        PerformIdentityResolverSetup::class,
        CleanupServiceOverrides::class,
        SetupServiceOverrides::class,
        RefreshTenantAwareDependencies::class,
    ],

];
