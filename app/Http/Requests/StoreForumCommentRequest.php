<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreForumCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_forum_posts');
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
