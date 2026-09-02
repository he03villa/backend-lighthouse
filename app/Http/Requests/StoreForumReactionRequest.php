<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreForumReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_forum_posts');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['like', 'heart', 'helpful'])],
        ];
    }
}
