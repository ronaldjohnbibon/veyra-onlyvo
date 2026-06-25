<?php

namespace App\Providers;

use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Shared\Mail\SharedEmailSender;
use App\Shared\Routing\FrontendUrlGenerator;
use FilesystemIterator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->loadMigrationsFrom(app_path('Tenant/Auth/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/AuditLogs/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/DesignRequests/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Templates/Posts/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Sidebar/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/SystemSettings/Database/Migrations'));
        $this->loadMigrationsFrom($this->templateMigrationPaths());
        $this->loadMigrationsFrom(app_path('Tenant/Tenants/Database/Migrations'));
        $this->loadMigrationsFrom(app_path('Tenant/Users/Database/Migrations'));

        app(SystemSettingService::class)->applyRuntimeConfig();

        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(30)->by($this->rateLimitKey($request, 'admin-login')));
        RateLimiter::for('tenant-login', fn (Request $request) => Limit::perMinute(30)->by($this->rateLimitKey($request, 'tenant-login')));
        RateLimiter::for('tenant-registration', fn (Request $request) => Limit::perHour(5)->by($this->rateLimitKey($request, 'tenant-registration')));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perHour(5)->by($this->rateLimitKey($request, 'password-reset')));
        RateLimiter::for('public-analytics', fn (Request $request) => Limit::perMinute(120)->by($this->rateLimitKey($request, 'public-analytics')));
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(10)->by($this->rateLimitKey($request, 'public-forms')));

        $resetUrl = function (object $notifiable, string $token): string {
            return app(FrontendUrlGenerator::class)->tenant(
                'web.tenant.password.reset',
                $notifiable->tenant->subdomain,
                [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ],
            );
        };

        ResetPassword::createUrlUsing($resetUrl);
        ResetPassword::toMailUsing(function (object $notifiable, string $token) use ($resetUrl): MailMessage {
            $settings = app(SystemSettingService::class);
            $replyTo  = app(SharedEmailSender::class)->replyToAddress(
                $settings->string('general.support_email'),
            );

            return (new MailMessage)
                ->replyTo($replyTo)
                ->subject(Lang::get('Reset Password Notification'))
                ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
                ->action(Lang::get('Reset Password'), $resetUrl($notifiable, $token))
                ->line(Lang::get('This password reset link will expire in :count minutes.', [
                    'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]))
                ->line(Lang::get('If you did not request a password reset, no further action is required.'));
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

    private function rateLimitKey(Request $request, string $bucket): string
    {
        return $bucket.'|'.$request->getHost().'|'.$request->ip();
    }
}
