<?php

namespace App\Modules\Templates\Http\Requests;

use App\Modules\Templates\Models\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Sprout\Contracts\Tenant as CurrentTenant;

class TemplateCtaSubmissionRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    private const CTA_TYPES = [
        'contact_message',
        'quote_request',
        'booking',
        'consultation',
        'support',
        'registration',
        'newsletter',
        'event_registration',
        'job_application',
        'feedback',
        'product_inquiry',
        'service_request',
        'demo_request',
        'lead_capture',
        'file_download',
        'donation',
        'callback_request',
        'custom_form',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_id' => [
                'required',
                'uuid',
                Rule::exists('templates', 'id')->where(function ($query): void {
                    $query
                        ->where('tenant_id', $this->tenantId())
                        ->where('status', 'published');
                }),
            ],
            'cta_type' => ['required', 'string', Rule::in(self::CTA_TYPES)],
            'payload'  => ['required', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $template = $this->template();

            if (! $template) {
                return;
            }

            $cta = $this->primaryCtaConfig($template);

            if (! $cta) {
                return;
            }

            if (($cta['type'] ?? null) !== $this->input('cta_type')) {
                $validator->errors()->add('cta_type', 'The CTA type does not match this template.');

                return;
            }

            $payloadValidator = Validator::make(
                $this->input('payload', []),
                $this->payloadRules($cta['fields'] ?? [])
            );

            if ($payloadValidator->fails()) {
                foreach ($payloadValidator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add("payload.$field", $message);
                    }
                }
            }
        });
    }

    public function template(): ?Template
    {
        return Template::query()
            ->whereKey($this->input('template_id'))
            ->where('status', 'published')
            ->first();
    }

    /**
     * @param  array<int, mixed>  $fields
     * @return array<string, mixed>
     */
    private function payloadRules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $rules[$key] = array_merge(
                ! empty($field['required']) ? ['required'] : ['nullable'],
                $this->rulesForFieldType((string) ($field['type'] ?? 'text'))
            );
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private function rulesForFieldType(string $type): array
    {
        return match ($type) {
            'email'    => ['email', 'max:255'],
            'url'      => ['url', 'max:2048'],
            'number'   => ['numeric'],
            'date'     => ['date'],
            'checkbox' => ['boolean'],
            default    => ['string', 'max:5000'],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function primaryCtaConfig(Template $template): ?array
    {
        $content = is_array($template->content) ? $template->content : [];
        $cta     = $content['primary_cta'] ?? null;

        return is_array($cta) ? $cta : null;
    }

    private function tenantId(): string
    {
        return (string) app(CurrentTenant::class)->getTenantKey();
    }
}
