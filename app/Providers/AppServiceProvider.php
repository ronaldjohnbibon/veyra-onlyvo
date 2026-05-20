<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Modules/Auth/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/Sidebar/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/Templates/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/Tenant/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/User/Database/Migrations'));

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $tenant      = $notifiable->tenant;
            $host        = request()->getHost();
            $scheme      = request()->getScheme();
            $port        = request()->getPort();
            $portSegment = in_array($port, [80, 443], true) ? '' : ':'.$port;

            if ($tenant && ! str_starts_with($host, $tenant->subdomain.'.')) {
                $host = $tenant->subdomain.'.'.$host;
            }

            return $scheme.'://'.$host.$portSegment.'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
