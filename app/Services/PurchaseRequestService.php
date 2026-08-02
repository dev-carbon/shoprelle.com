<?php

namespace App\Services;

use App\DataTransferObjects\PurchaseItemData;
use App\DataTransferObjects\PurchaseRequestData;
use App\Enums\PurchaseRequestStatus;
use App\Exceptions\ConversationException;
use App\Models\PurchaseRequest;
use App\Notifications\PurchaseRequestSubmitted;
use App\Repositories\Contracts\CustomerRepository;
use Illuminate\Support\Facades\DB;

/**
 * Turns a completed conversation into a persisted purchase request.
 *
 * This is the single entry point for creating requests: the chatbot uses it
 * today, and an API or an admin-side manual form would use the same method.
 */
class PurchaseRequestService
{
    public function __construct(
        private CustomerRepository $customers,
        private AttachmentService $attachments,
        private NotificationService $notifications,
    ) {}

    /**
     * Persist a request, its items and its screenshots atomically.
     *
     * @throws ConversationException when the request carries no item
     */
    public function submit(PurchaseRequestData $data): PurchaseRequest
    {
        if ($data->items === []) {
            throw ConversationException::noItems();
        }

        $maxItems = (int) config('shoprelle.requests.max_items');

        if ($data->itemCount() > $maxItems) {
            throw ConversationException::tooManyItems($maxItems);
        }

        $request = DB::transaction(function () use ($data): PurchaseRequest {
            $customer = $this->customers->updateOrCreate($data->customer);

            $request = $customer->purchaseRequests()->create([
                'reference' => PurchaseRequest::generateReference(),
                'status' => PurchaseRequestStatus::New,
                'country' => $data->country(),
                'city' => $data->city(),
                'channel' => $data->channel,
                'customer_comment' => $data->comment,
            ]);

            foreach ($data->items as $position => $itemData) {
                $this->createItem($request, $itemData, $position);
            }

            $request->statusHistories()->create([
                'from_status' => null,
                'to_status' => PurchaseRequestStatus::New,
                'user_id' => null,
                'comment' => 'Demande créée par le client.',
            ]);

            // Attach the very instance we just used, so a freshly generated
            // access code survives out of here. Lazy-loading the relation would
            // return a second instance, and the code in clear is not a column.
            $request->setRelation('customer', $customer);

            return $request;
        });

        // Notifying outside the transaction keeps a failing channel from rolling
        // back a request the customer has already been told was accepted.
        $this->notifications->notifyAdministrators(
            new PurchaseRequestSubmitted($request->loadMissing(['customer', 'items'])),
        );

        return $request;
    }

    private function createItem(PurchaseRequest $request, PurchaseItemData $data, int $position): void
    {
        $item = $request->items()->create($data->toAttributes($position));

        foreach ($data->attachments as $pending) {
            $this->attachments->attachToItem($pending, $item);
        }
    }
}
