<?php

namespace App\Shared\Routing;

class FrontendUrlGenerator
{
    /**
     * @param  array<string, scalar|null>  $query
     */
    public function tenant(string $routeName, string $subdomain, array $query = []): string
    {
        $domain = trim((string) config('multitenancy.resolvers.subdomain.domain'));
        $host   = $domain !== '' ? $domain : $this->appHost();

        return $this->absoluteUrl($subdomain.'.'.$host, route($routeName, [], false), $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    private function absoluteUrl(string $host, string $path, array $query): string
    {
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port   = parse_url($appUrl, PHP_URL_PORT);
        $url    = $scheme.'://'.$host.($port ? ':'.$port : '').'/'.ltrim($path, '/');
        $query  = array_filter($query, fn (mixed $value): bool => $value !== null && $value !== '');

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    private function appHost(): string
    {
        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
    }
}
