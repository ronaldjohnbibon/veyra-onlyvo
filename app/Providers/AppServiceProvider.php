<?php

namespace App\Providers;

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
        $this->loadMigrationsFrom(app_path('Modules/Analytics/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/Auth/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/DesignRequests/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/Posts/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Modules/Sidebar/Database/Migrations'));
        $this->loadMigrationsFrom($this->templateMigrationPaths());
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

    /**
     * Return shared and template-specific migration paths.
     *
     * @return array<int, string>
     */
    private function templateMigrationPaths(): array
    {
        $basePath     = app_path('Modules/Templates/Database/Migrations');
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
