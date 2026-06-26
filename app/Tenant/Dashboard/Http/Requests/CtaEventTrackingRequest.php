<?php

namespace App\Tenant\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CtaEventTrackingRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    public const EVENT_TYPES = [
        'cta_view',
        'button_click',
        'link_click',
        'form_opened',
        'form_submitted',
        'booking_submitted',
        'message_submitted',
        'quote_request_submitted',
        'newsletter_signup_submitted',
        'phone_click',
        'email_click',
        'whatsapp_click',
        'social_media_click',
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
            'template_id'    => ['required', 'uuid'],
            'cta_identifier' => ['required', 'string', 'max:191'],
            'cta_label'      => ['nullable', 'string', 'max:255'],
            'cta_type'       => ['required', 'string', 'max:100'],
            'event_type'     => ['required', 'string', Rule::in(self::EVENT_TYPES)],
            'url'            => ['required', 'string', 'max:2000'],
            'referrer'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required'    => 'The template identifier is required to record this event.',
            'template_id.uuid'        => 'The template identifier is invalid.',
            'cta_identifier.required' => 'The CTA identifier is required.',
            'cta_identifier.string'   => 'The CTA identifier must be text.',
            'cta_identifier.max'      => 'The CTA identifier may not exceed :max characters.',
            'cta_label.string'        => 'The CTA label must be text.',
            'cta_label.max'           => 'The CTA label may not exceed :max characters.',
            'cta_type.required'       => 'The CTA type is required.',
            'cta_type.string'         => 'The CTA type must be text.',
            'cta_type.max'            => 'The CTA type may not exceed :max characters.',
            'event_type.required'     => 'The event type is required.',
            'event_type.string'       => 'The event type must be text.',
            'event_type.in'           => 'Select a valid CTA event type.',
            'url.required'            => 'The event URL is required.',
            'url.string'              => 'The event URL must be text.',
            'url.max'                 => 'The event URL may not exceed :max characters.',
            'referrer.string'         => 'The referring URL must be text.',
            'referrer.max'            => 'The referring URL may not exceed :max characters.',
        ];
    }
}
