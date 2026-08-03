<?php

namespace App\Providers;

use App\Chatbot\ChannelConversationStore;
use App\Chatbot\Contracts\ConversationStore;
use App\Models\Customer;
use App\Models\PurchaseRequest;
use App\Models\Review;
use App\Policies\CustomerPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\ReviewPolicy;
use App\Repositories\Contracts\CustomerRepository;
use App\Repositories\Contracts\PurchaseRequestRepository;
use App\Repositories\Eloquent\EloquentCustomerRepository;
use App\Repositories\Eloquent\EloquentPurchaseRequestRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Shoprelle domain: repository bindings, authorization and the rate
 * limits protecting the public chatbot endpoints.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * Every consumer depends on the contract, never on the Eloquent class, so
     * a repository can be swapped or decorated (cache, read replica) here alone.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CustomerRepository::class => EloquentCustomerRepository::class,
        PurchaseRequestRepository::class => EloquentPurchaseRequestRepository::class,
        // Storage follows the channel: session for the browser, cache for the
        // webhook channels that have no session of their own.
        ConversationStore::class => ChannelConversationStore::class,
    ];

    public function boot(): void
    {
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);

        $this->configureRateLimiting();
    }

    /**
     * The chatbot is unauthenticated by design, so it is throttled per IP and
     * per session to keep a single visitor from flooding the back office.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('chatbot', fn (Request $request) => [
            Limit::perMinute(60)->by('chat-ip:'.$request->ip()),
            Limit::perMinute(40)->by('chat-session:'.$request->session()->getId()),
        ]);

        RateLimiter::for('chatbot-submit', fn (Request $request) => [
            Limit::perHour(10)->by('submit-ip:'.$request->ip()),
            Limit::perDay(30)->by('submit-ip-day:'.$request->ip()),
        ]);

        RateLimiter::for('chatbot-upload', fn (Request $request) => [
            Limit::perMinute(10)->by('upload-ip:'.$request->ip()),
            Limit::perHour(60)->by('upload-ip-hour:'.$request->ip()),
        ]);

        // The per-number budget lives in CustomerAccessService and is what
        // actually guards the code. This one only stops a single machine from
        // sweeping many numbers at once.
        RateLimiter::for('customer-access', fn (Request $request) => [
            Limit::perMinute(5)->by('customer-access-ip:'.$request->ip()),
            Limit::perHour(30)->by('customer-access-ip-hour:'.$request->ip()),
        ]);

        // Telegram delivers every customer's updates from its own servers, so
        // the limit is generous and scoped per chat rather than per IP.
        RateLimiter::for('telegram', function (Request $request) {
            $chatId = $request->input('message.chat.id')
                ?? $request->input('callback_query.message.chat.id');

            return [
                Limit::perMinute(600),
                Limit::perMinute(30)->by('telegram-chat:'.$chatId),
            ];
        });
    }
}
