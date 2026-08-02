<?php

use App\Models\Attachment;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->admin()->create();
    $this->request = PurchaseRequest::factory()->create();
    $this->item = PurchaseItem::factory()->for($this->request)->create();

    Storage::disk('local')->put('purchase-requests/1/capture.jpg', 'binary-content');

    $this->attachment = Attachment::factory()->create([
        'purchase_request_id' => $this->request->id,
        'purchase_item_id' => $this->item->id,
        'path' => 'purchase-requests/1/capture.jpg',
    ]);
});

it('serves a screenshot to an administrator', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.attachments.show', [
            'purchaseRequest' => $this->request->reference,
            'attachment' => $this->attachment->id,
        ]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('refuses a screenshot to a guest', function () {
    $this->get(route('admin.attachments.show', [
        'purchaseRequest' => $this->request->reference,
        'attachment' => $this->attachment->id,
    ]))->assertRedirect(route('login'));
});

it('refuses a screenshot to a non-administrator', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.attachments.show', [
            'purchaseRequest' => $this->request->reference,
            'attachment' => $this->attachment->id,
        ]))
        ->assertForbidden();
});

it('does not serve an attachment through another request', function () {
    $otherRequest = PurchaseRequest::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.attachments.show', [
            'purchaseRequest' => $otherRequest->reference,
            'attachment' => $this->attachment->id,
        ]))
        ->assertNotFound();
});

it('returns 404 when the stored file is gone', function () {
    Storage::disk('local')->delete($this->attachment->path);

    $this->actingAs($this->admin)
        ->get(route('admin.attachments.show', [
            'purchaseRequest' => $this->request->reference,
            'attachment' => $this->attachment->id,
        ]))
        ->assertNotFound();
});

it('never exposes uploads on the public disk', function () {
    expect(config('shoprelle.attachments.disk'))->not->toBe('public')
        ->and(Storage::disk('local')->url(...))->toBeCallable();
});
