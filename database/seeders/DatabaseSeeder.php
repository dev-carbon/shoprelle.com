<?php

namespace Database\Seeders;

use App\Enums\Marketplace;
use App\Enums\PurchaseRequestStatus;
use App\Models\AdminNote;
use App\Models\Customer;
use App\Models\PurchaseItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Shoprelle',
            'email' => 'admin@shoprelle.com',
        ]);

        // Enough volume for the filters, the pagination and the status badges to
        // be exercised on a fresh install.
        Customer::factory()
            ->count(12)
            ->create()
            ->each(function (Customer $customer) use ($admin): void {
                $requests = PurchaseRequest::factory()
                    ->count(fake()->numberBetween(1, 3))
                    ->for($customer)
                    ->create([
                        'status' => fake()->randomElement(PurchaseRequestStatus::cases()),
                        'country' => $customer->country,
                        'city' => $customer->city,
                    ]);

                foreach ($requests as $request) {
                    PurchaseItem::factory()
                        ->count(fake()->numberBetween(1, 3))
                        ->for($request)
                        ->on(fake()->randomElement(Marketplace::cases()))
                        ->create();

                    $request->statusHistories()->create([
                        'from_status' => null,
                        'to_status' => PurchaseRequestStatus::New,
                        'comment' => 'Demande créée par le client.',
                    ]);

                    if ($request->status !== PurchaseRequestStatus::New) {
                        $request->statusHistories()->create([
                            'from_status' => PurchaseRequestStatus::New,
                            'to_status' => $request->status,
                            'user_id' => $admin->id,
                        ]);
                    }

                    if (fake()->boolean(30)) {
                        AdminNote::factory()->for($request)->create(['user_id' => $admin->id]);
                    }
                }
            });
    }
}
