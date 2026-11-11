<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $rules = [
            'version' => ['required', 'integer', 'min:1'],
            'content.contact_email' => ['nullable', 'email', 'max:255'],
            'content.chat_enabled' => ['required', 'boolean'],
            'content.themes' => ['required', 'array', 'min:1', 'max:30'],
            'content.themes.*.id' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', 'max:100', 'distinct'],
            'content.themes.*.name' => ['required', 'string', 'max:150'],
            'content.themes.*.eyebrow' => ['required', 'string', 'max:150'],
            'content.themes.*.names' => ['required', 'array', 'min:1', 'max:3'],
            'content.themes.*.names.*' => ['required', 'string', 'max:100'],
            'content.themes.*.caption' => ['required', 'string', 'max:150'],
            'content.themes.*.preview_url' => ['nullable', 'url:http,https', 'max:2048'],
            'content.themes.*.alt' => ['nullable', 'string', 'max:255'],
            'content.features' => ['required', 'array', 'min:1', 'max:12'],
            'content.features.*.title' => ['required', 'string', 'max:150'],
            'content.features.*.description' => ['required', 'string', 'max:500'],
        ];
        foreach (['brand', 'title', 'description', 'intro_title', 'intro_emphasis', 'hero_title', 'hero_emphasis', 'hero_description', 'collection_title', 'features_title', 'steps_title', 'steps_description', 'order_title', 'order_button', 'footer_tagline'] as $field) {
            $rules['content.'.$field] = ['required', 'string', 'max:1000'];
        }

        return $rules;
    }
}
