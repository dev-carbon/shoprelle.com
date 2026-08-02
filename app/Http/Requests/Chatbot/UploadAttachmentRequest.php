<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class UploadAttachmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `File::image()` inspects the real MIME type rather than trusting the
     * client, and SVG stays disallowed to keep script payloads out.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'screenshot' => [
                'required',
                File::image()
                    ->types(config('shoprelle.attachments.allowed_extensions'))
                    ->max(config('shoprelle.attachments.max_size_kilobytes').'kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'screenshot.required' => 'Merci de sélectionner une image.',
            'screenshot.image' => 'Le fichier doit être une image.',
            'screenshot.max' => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }

    public function screenshot(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('screenshot');

        return $file;
    }
}
