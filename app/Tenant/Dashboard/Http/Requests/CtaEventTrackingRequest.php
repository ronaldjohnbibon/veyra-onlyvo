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
}
