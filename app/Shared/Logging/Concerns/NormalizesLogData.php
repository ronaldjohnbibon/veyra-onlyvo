<?php

namespace App\Shared\Logging\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use BackedEnum;
use UnitEnum;

trait NormalizesLogData
{
    /**
     * @var array<int, string>
     */
    private array $sensitiveLogKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'apikey',
        'secret',
        'client_secret',
        'private_key',
        'authorization',
        'cookie',
        'remember_token',
        'plain_text_token',
        'smtp_password',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizedPayload(array $data, ?Authenticatable $actor = null, ?Request $request = null): array
    {
        return [
            'scope'          => $this->normalizeString($data['scope'] ?? null, $this->defaultLogScope()),
            'tenant_id'      => $this->normalizeNullableString($data['tenant_id'] ?? data_get($actor, 'tenant_id')),
            'category'       => $this->normalizeString($data['category'] ?? null, $this->categoryForAction((string) ($data['action'] ?? 'activity'))),
            'action'         => $this->normalizeString($data['action'] ?? null, 'activity.recorded'),
            'severity'       => $this->normalizeString($data['severity'] ?? null, 'info'),
            'actor_type'     => $this->normalizeString($data['actor_type'] ?? null, $this->actorType($actor)),
            'actor_id'       => $this->normalizeNullableString($data['actor_id'] ?? $actor?->getAuthIdentifier()),
            'actor_name'     => $this->normalizeNullableString($data['actor_name'] ?? $this->actorName($actor)),
            'actor_email'    => $this->normalizeNullableString($data['actor_email'] ?? data_get($actor, 'email')),
            'entity_type'    => $this->normalizeString($data['entity_type'] ?? null, 'system'),
            'entity_id'      => $this->normalizeNullableString($data['entity_id'] ?? null),
            'entity_label'   => $this->normalizeNullableString($data['entity_label'] ?? null),
            'ip_address'     => $this->normalizeNullableString($data['ip_address'] ?? $request?->ip()),
            'user_agent'     => $this->normalizeNullableString($data['user_agent'] ?? $request?->userAgent()),
            'previous_value' => $this->redactValue($this->normalizeValue($data['previous_value'] ?? null)),
            'new_value'      => $this->redactValue($this->normalizeValue($data['new_value'] ?? null)),
            'metadata'       => $this->redactValue($this->normalizeValue($data['metadata'] ?? null)),
            'occurred_at'    => $this->normalizeTimestamp($data['occurred_at'] ?? null),
        ];
    }

    protected function defaultLogScope(): string
    {
        return 'system';
    }

    protected function entityType(Model $model): string
    {
        return str($model::class)->afterLast('\\')->snake()->toString();
    }

    protected function entityLabel(Model $model): ?string
    {
        foreach (['name', 'title', 'label', 'key', 'email', 'subdomain', 'slug'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if ($value) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function modelPayload(Model $model): array
    {
        return $this->redactValue($model->attributesToArray());
    }

    protected function categoryForAction(string $action): string
    {
        return match (true) {
            Str::contains($action, ['login', 'logout', 'password', 'session']) => 'auth',
            Str::contains($action, ['permission', 'security', 'blocked', 'unauthorized']) => 'permission',
            Str::contains($action, ['error', 'failed', 'exception']) => 'error',
            Str::contains($action, ['notification', 'email', 'notify']) => 'notification',
            Str::contains($action, ['upload', 'file', 'image']) => 'file_upload',
            Str::contains($action, ['setting', 'sidebar', 'system']) => 'system',
            Str::contains($action, ['payment', 'transaction', 'billing']) => 'transaction',
            Str::contains($action, ['created', 'updated', 'deleted', 'restored', 'published', 'unpublished', 'deactivated', 'reactivated']) => 'audit',
            default => 'activity',
        };
    }

    protected function redactValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            $value = $value->attributesToArray();
        }

        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $key => $item) {
            $keyString = is_string($key) ? $key : (string) $key;

            if ($this->isSensitiveLogKey($keyString)) {
                $redacted[$key] = $this->redactedMarker($item);

                continue;
            }

            $redacted[$key] = $this->redactValue($item);
        }

        return $redacted;
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->attributesToArray();
        }

        return $value;
    }

    private function actorType(?Authenticatable $actor): string
    {
        if (! $actor) {
            return 'system';
        }

        $type = data_get($actor, 'user_type');

        if ($type instanceof BackedEnum) {
            return (string) $type->value;
        }

        if ($type instanceof UnitEnum) {
            return $type->name;
        }

        return $type ? (string) $type : 'user';
    }

    private function actorName(?Authenticatable $actor): ?string
    {
        if (! $actor) {
            return null;
        }

        $fullName = trim((string) data_get($actor, 'first_name').' '.(string) data_get($actor, 'last_name'));

        return $fullName !== '' ? $fullName : data_get($actor, 'name');
    }

    private function normalizeString(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text === '' ? $fallback : $text;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function normalizeTimestamp(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value) {
            return Carbon::parse($value);
        }

        return now();
    }

    private function isSensitiveLogKey(string $key): bool
    {
        $normalized = Str::of($key)->lower()->replace(['-', ' '], '_')->toString();

        foreach ($this->sensitiveLogKeys as $sensitiveKey) {
            if ($normalized === $sensitiveKey || Str::contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function redactedMarker(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return '[redacted]';
    }
}
