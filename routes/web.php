<?php

use App\Http\Controllers\Admin\AdminNoteController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\CustomerAccessCodeController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PurchaseRequestController;
use App\Http\Controllers\Admin\PurchaseRequestStatusController;
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Telegram\WebhookController as TelegramWebhookController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\VerifyTelegramWebhook;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Les pages publiques, pour les moteurs. Référencé depuis public/robots.txt.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Purchase Request Assistant
|--------------------------------------------------------------------------
|
| Public and unauthenticated: a customer creates a request by talking to the
| bot. Every endpoint is throttled, and confirmations and uploads carry their
| own tighter limits.
|
*/

Route::prefix('demande')->name('chat.')->group(function () {
    Route::get('/', [ChatbotController::class, 'show'])->name('show');

    Route::middleware('throttle:chatbot')->group(function () {
        Route::post('lien', [ChatbotController::class, 'link'])->name('link');
        Route::post('message', [ChatbotController::class, 'message'])->name('message');
        Route::post('passer', [ChatbotController::class, 'skip'])->name('skip');
        Route::post('menu', [ChatbotController::class, 'menu'])->name('menu');
        Route::post('recommencer', [ChatbotController::class, 'restart'])->name('restart');
    });

    Route::post('capture', [ChatbotController::class, 'upload'])
        ->middleware('throttle:chatbot-upload')
        ->name('upload');

    Route::post('confirmer', [ChatbotController::class, 'confirm'])
        ->middleware('throttle:chatbot-submit')
        ->name('confirm');
});

/*
|--------------------------------------------------------------------------
| Telegram Channel
|--------------------------------------------------------------------------
|
| Same conversation engine as the web assistant, reached over a webhook. The
| shared secret is verified before anything else runs; the CSRF exemption for
| this path is declared in bootstrap/app.php.
|
*/

Route::post('telegram/webhook', TelegramWebhookController::class)
    ->middleware([VerifyTelegramWebhook::class, 'throttle:telegram'])
    ->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Back Office
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('demandes', [PurchaseRequestController::class, 'index'])->name('requests.index');

        Route::get('clients', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('clients/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('clients/{customer}/code', CustomerAccessCodeController::class)->name('customers.code.store');

        Route::get('avis', ReviewController::class)->name('reviews.index');

        Route::get('statistiques', StatisticsController::class)->name('statistics');

        Route::prefix('demandes/{purchaseRequest}')->group(function () {
            Route::get('/', [PurchaseRequestController::class, 'show'])->name('requests.show');
            Route::put('statut', [PurchaseRequestStatusController::class, 'update'])->name('requests.status.update');
            Route::post('devis', [QuoteController::class, 'store'])->name('requests.quote.store');
            Route::post('paiements', [PaymentController::class, 'store'])->name('requests.payments.store');
            Route::post('notes', [AdminNoteController::class, 'store'])->name('notes.store');
            Route::get('captures/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
        });
    });
});

require __DIR__.'/settings.php';
