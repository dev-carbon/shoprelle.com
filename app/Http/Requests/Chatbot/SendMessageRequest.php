<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The message is deliberately capped well below the longest field the flow
     * accepts (a product URL); per-step rules live in the chatbot engine.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['present', 'string', 'max:2048'],
        ];
    }

    public function message(): string
    {
        return (string) $this->string('message');
    }
}
