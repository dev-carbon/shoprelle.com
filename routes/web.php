<?php

use App\Http\Controllers\Admin\AdminNoteController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CustomerAccessCodeController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseRequestController;
use App\Http\Controllers\Admin\PurchaseRequestStatusController;
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\ReviewApprovalController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CustomerAccessController;
use App\Http\Controllers\CustomerAttachmentController;
use App\Http\Controllers\CustomerQuoteController;
use App\Http\Controllers\CustomerRequestController;
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

    /*
     * Ouvrir l'assistant sur « nous écrire », depuis la page d'une demande. La
     * référence n'est qu'un sujet de message : elle n'ouvre aucun accès, et ce
     * qui donne accès à une demande reste le numéro et le code.
     */
    Route::get('ecrire/{reference?}', [ChatbotController::class, 'contact'])
        ->middleware('throttle:chatbot')
        ->name('contact');

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
| Customer Area
|--------------------------------------------------------------------------
|
| "Mes demandes": a customer reads their own quotes after proving themselves
| with their phone number and the access code handed out on their first
| request. One address whether they are identified or not — it is what they
| bookmark, and it has to keep working once the session has expired.
|
*/

Route::prefix('mes-demandes')->name('orders.')->group(function () {
    Route::get('/', [CustomerRequestController::class, 'index'])->name('index');
    Route::get('{reference}', [CustomerRequestController::class, 'show'])->name('show');

    // Répondre au devis. Sous le même préfixe et derrière la même session : la
    // demande est retrouvée à partir du client identifié, jamais de la seule
    // référence.
    Route::post('{reference}/acceptation', [CustomerQuoteController::class, 'accept'])
        ->name('quote.accept');
    Route::post('{reference}/refus', [CustomerQuoteController::class, 'decline'])
        ->name('quote.decline');

    /*
     * Les captures que le client nous a envoyées, rendues à leur auteur. Sous
     * le même préfixe et la même session que le reste : la demande est
     * retrouvée à partir du client identifié, jamais de la seule référence.
     */
    Route::get('{reference}/captures/{attachment}', [CustomerAttachmentController::class, 'show'])
        ->name('attachments.show');

    Route::post('acces', [CustomerAccessController::class, 'store'])
        ->middleware('throttle:customer-access')
        ->name('access.store');

    Route::post('deconnexion', [CustomerAccessController::class, 'destroy'])->name('access.destroy');
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

        Route::get('produits', [ProductController::class, 'index'])->name('products.index');
        Route::post('produits', [ProductController::class, 'store'])->name('products.store');
        Route::put('produits/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('produits/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::patch('messages/{contactMessage}', [ContactMessageController::class, 'update'])->name('messages.update');

        Route::get('avis', ReviewController::class)->name('reviews.index');
        Route::patch('avis/{review}/publication', ReviewApprovalController::class)
            ->name('reviews.approval');

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
