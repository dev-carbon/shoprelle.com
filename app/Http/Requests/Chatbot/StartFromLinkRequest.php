<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A product link pasted on the landing page, before any conversation exists.
 */
class StartFromLinkRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Collez le lien du produit qui vous intéresse.',
            'url.url' => 'Ce lien ne semble pas valide. Collez l\'adresse complète du produit, en commençant par https://',
            'url.max' => 'Ce lien est trop long. Copiez celui de la page produit depuis la barre d\'adresse.',
        ];
    }

    public function url(): string
    {
        return trim((string) $this->string('url'));
    }
}
