<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateForumPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_forum_posts');
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'pinned' => ['sometimes', 'boolean'],
        ];
    }
}
