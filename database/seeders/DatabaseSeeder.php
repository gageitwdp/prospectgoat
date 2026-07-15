<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $account = Account::firstOrCreate(
            ['slug' => 'prospectgoat-default'],
            [
                'name' => 'ProspectGoat Default',
                'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
                'billing_status' => Account::BILLING_STATUS_PENDING,
            ]
        );

        User::updateOrCreate(
            ['email' => 'gage@prospectgoat.com'],
            [
                'name' => 'Gage',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'ChangeMe123!')),
                'email_verified_at' => now(),
                'account_id' => $account->id,
                'role' => 'owner',
            ]
        );

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'account_id' => $account->id,
                'role' => 'agent',
            ]
        );
    }
}
