<?php

namespace App\Http\Requests;

use App\Chatbot\ChatbotEngine;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape only. Whether the pair actually opens an account is decided after
     * validation, and answered the same way whatever the reason it does not.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'numéro de téléphone',
            'code' => "code d'accès",
        ];
    }

    /**
     * The number as the chatbot stored it, so a customer who typed it one way
     * there and another way here still lands on their own account.
     */
    public function phone(): string
    {
        return app(ChatbotEngine::class)->normalizePhone($this->string('phone')->value());
    }

    public function code(): string
    {
        return $this->string('code')->value();
    }
}
