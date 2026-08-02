<?php

namespace App\Http\Requests\Admin;

use App\DataTransferObjects\PaymentData;
use App\Enums\PaymentMethod;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordPayment', $this->route('purchaseRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],

            // Locked to the currency the request was quoted in. Instalments are
            // summed to decide whether the quote is covered, and that sum is
            // only meaningful if every line shares one currency.
            'currency' => ['required', 'string', Rule::in([$this->quoteCurrency()])],

            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'provider' => ['nullable', 'string', 'max:60'],
            'provider_reference' => ['nullable', 'string', 'max:120'],

            // A payment cannot land in the future, and is usually keyed in the
            // morning after it arrived.
            'received_at' => ['required', 'date', 'before_or_equal:now'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'montant',
            'currency' => 'devise',
            'method' => 'moyen de paiement',
            'provider' => 'opérateur',
            'provider_reference' => 'référence de transaction',
            'received_at' => 'date de réception',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.in' => 'Le paiement doit être dans la devise du devis ('.$this->quoteCurrency().').',
            'received_at.before_or_equal' => 'La date de réception ne peut pas être dans le futur.',
        ];
    }

    public function toPaymentData(): PaymentData
    {
        return PaymentData::fromArray([
            'amount' => $this->input('amount'),
            'currency' => (string) $this->string('currency'),
            'method' => (string) $this->string('method'),
            'received_at' => $this->input('received_at'),
            'provider' => $this->input('provider'),
            'provider_reference' => $this->input('provider_reference'),
            'notes' => $this->input('notes'),
        ]);
    }

    /**
     * The currency of the quote being settled, falling back to the configured
     * default so validation still has something to compare against.
     */
    private function quoteCurrency(): string
    {
        $purchaseRequest = $this->route('purchaseRequest');

        return $purchaseRequest instanceof PurchaseRequest
            ? ($purchaseRequest->quote_currency ?? config('shoprelle.quote_currency'))
            : config('shoprelle.quote_currency');
    }
}
