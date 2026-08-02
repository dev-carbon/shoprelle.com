<?php

namespace App\Http\Requests\Admin;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('purchaseRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Only the transitions the current status allows are accepted, so an
     * out-of-date form cannot skip a stage of the lifecycle.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PurchaseRequest $purchaseRequest */
        $purchaseRequest = $this->route('purchaseRequest');

        return [
            'status' => [
                'required',
                Rule::enum(PurchaseRequestStatus::class)
                    ->only($purchaseRequest->status->allowedTransitions()),
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Ce changement de statut n\'est pas autorisé.',
        ];
    }

    public function status(): PurchaseRequestStatus
    {
        return $this->enum('status', PurchaseRequestStatus::class);
    }

    public function comment(): ?string
    {
        $comment = trim((string) $this->string('comment'));

        return $comment === '' ? null : $comment;
    }
}
