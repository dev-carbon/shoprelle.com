<?php

use App\Enums\PurchaseRequestStatus;

it('walks the happy path one stage at a time', function () {
    $path = [
        PurchaseRequestStatus::New,
        PurchaseRequestStatus::Pending,
        PurchaseRequestStatus::QuoteSent,
        PurchaseRequestStatus::PaymentReceived,
        PurchaseRequestStatus::Purchased,
        PurchaseRequestStatus::Preparing,
        PurchaseRequestStatus::Shipped,
        PurchaseRequestStatus::Delivered,
    ];

    foreach ($path as $index => $status) {
        $next = $path[$index + 1] ?? null;

        if ($next !== null) {
            expect($status->canTransitionTo($next))->toBeTrue();
        }
    }
});

it('never allows skipping ahead in the lifecycle', function () {
    expect(PurchaseRequestStatus::New->canTransitionTo(PurchaseRequestStatus::Delivered))->toBeFalse()
        ->and(PurchaseRequestStatus::New->canTransitionTo(PurchaseRequestStatus::Shipped))->toBeFalse()
        ->and(PurchaseRequestStatus::Pending->canTransitionTo(PurchaseRequestStatus::Purchased))->toBeFalse();
});

it('never allows going backwards', function () {
    expect(PurchaseRequestStatus::Shipped->canTransitionTo(PurchaseRequestStatus::Preparing))->toBeFalse()
        ->and(PurchaseRequestStatus::QuoteSent->canTransitionTo(PurchaseRequestStatus::New))->toBeFalse();
});

it('lets an administrator send a quote straight from a new request', function () {
    expect(PurchaseRequestStatus::New->canTransitionTo(PurchaseRequestStatus::QuoteSent))->toBeTrue();
});

it('lets a quote be revised by returning the request to pending', function () {
    expect(PurchaseRequestStatus::QuoteSent->canTransitionTo(PurchaseRequestStatus::Pending))->toBeTrue();
});

it('allows cancelling at every stage before shipping', function () {
    $cancellable = [
        PurchaseRequestStatus::New,
        PurchaseRequestStatus::Pending,
        PurchaseRequestStatus::QuoteSent,
        PurchaseRequestStatus::PaymentReceived,
        PurchaseRequestStatus::Purchased,
        PurchaseRequestStatus::Preparing,
    ];

    foreach ($cancellable as $status) {
        expect($status->canTransitionTo(PurchaseRequestStatus::Cancelled))
            ->toBeTrue("{$status->value} should be cancellable");
    }
});

it('refuses to cancel a request that has already shipped', function () {
    expect(PurchaseRequestStatus::Shipped->canTransitionTo(PurchaseRequestStatus::Cancelled))->toBeFalse()
        ->and(PurchaseRequestStatus::Delivered->canTransitionTo(PurchaseRequestStatus::Cancelled))->toBeFalse();
});

it('treats delivered and cancelled as closed', function () {
    expect(PurchaseRequestStatus::Delivered->isFinal())->toBeTrue()
        ->and(PurchaseRequestStatus::Cancelled->isFinal())->toBeTrue()
        ->and(PurchaseRequestStatus::New->isFinal())->toBeFalse();
});

it('gives every status a label and a colour', function () {
    foreach (PurchaseRequestStatus::cases() as $status) {
        expect($status->label())->not->toBeEmpty()
            ->and($status->color())->not->toBeEmpty();
    }
});
