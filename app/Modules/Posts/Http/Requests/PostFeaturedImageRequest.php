<?php

namespace App\Modules\Posts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostFeaturedImageRequest extends FormRequest
{
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ];
    }
}
