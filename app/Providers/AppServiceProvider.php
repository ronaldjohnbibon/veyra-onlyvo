<?php

namespace App\Providers;

use App\Admin\SystemSettings\Services\SystemSettingService;
use FilesystemIterator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
        $this->loadMigrationsFrom(app_path('Tenant/Dashboard/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Admin/AuditLogs/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Admin/DesignRequests/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Admin/Templates/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Auth/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/AuditLogs/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/DesignRequests/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Templates/Posts/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Sidebar/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/SystemSettings/Database/Migrations'));
        $this->loadMigrationsFrom($this->templateMigrationPaths());
        $this->loadMigrationsFrom(app_path('Tenant/TrackingLogs/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Tenants/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Users/Database/Migrations'));

        app(SystemSettingService::class)->applyRuntimeConfig();

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

    /**
     * Return shared and template-specific migration paths.
     *
     * @return array<int, string>
     */
    private function templateMigrationPaths(): array
    {
        $basePath     = app_path('Tenant/Templates/Database/Migrations');
        $templatePath = $basePath.DIRECTORY_SEPARATOR.'Templates';
        $paths        = [$basePath];

        if (! is_dir($templatePath)) {
            return $paths;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($templatePath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $paths[] = $file->getPathname();
            }
        }

        return $paths;
    }
}
