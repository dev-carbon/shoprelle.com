<?php

namespace App\Http\Requests\Admin;

use App\DataTransferObjects\QuoteData;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sendQuote', $this->route('purchaseRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // One amount per requested product, keyed by item id. The keys
            // themselves are checked in `after`, where the request is at hand.
            'items' => ['required', 'array'],
            'items.*' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'shipping_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Back-office only. The rate is required alongside a cost because
            // one without the other cannot produce a margin.
            'cost_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'cost_currency' => ['nullable', 'required_with:cost_amount', 'string', 'size:3', 'alpha'],
            'exchange_rate' => ['nullable', 'required_with:cost_amount', 'numeric', 'gt:0', 'max:9999999999'],
        ];
    }

    /**
     * A quote priced line by line only adds up if every line of the request is
     * priced, and no stray line sneaks in from another request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['items', 'items.*'])) {
                    return;
                }

                /** @var PurchaseRequest $purchaseRequest */
                $purchaseRequest = $this->route('purchaseRequest');

                $expected = $purchaseRequest->items->modelKeys();
                $submitted = array_map(intval(...), array_keys((array) $this->input('items')));

                sort($expected);
                sort($submitted);

                if ($expected !== $submitted) {
                    $validator->errors()->add('items', 'Chaque produit de la demande doit être chiffré.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items' => 'chiffrage des produits',
            'shipping_amount' => 'montant de la livraison',
            'currency' => 'devise',
            'cost_amount' => "coût d'achat",
            'cost_currency' => "devise d'achat",
            'exchange_rate' => 'taux de change',
        ];
    }

    public function toQuoteData(): QuoteData
    {
        $costCurrency = $this->string('cost_currency')->trim();

        return QuoteData::fromArray([
            'items' => (array) $this->input('items'),
            'shipping_amount' => $this->input('shipping_amount'),
            'currency' => strtoupper((string) $this->string('currency')),
            'notes' => $this->input('notes'),
            'cost_amount' => $this->input('cost_amount'),
            'cost_currency' => $costCurrency->isEmpty() ? null : strtoupper($costCurrency->value()),
            'exchange_rate' => $this->input('exchange_rate'),
        ]);
    }
}
