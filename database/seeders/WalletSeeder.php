<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $walletService = new WalletService();

        // Create main cashbox wallet
        echo "Creating main cashbox wallet...\n";
        $mainCashbox = Wallet::firstOrCreate(
            [
                'owner_type' => null,
                'owner_id' => null,
                'type' => Wallet::TYPE_MAIN_CASHBOX,
            ],
            [
                'name' => 'Main Cashbox',
                'receivable_amount' => 0,
                'payable_amount' => 0,
            ]
        );
        echo "Main cashbox wallet created: {$mainCashbox->name}\n";

        // Create staff wallets for all admins
        echo "\nCreating staff wallets for existing admins...\n";
        $admins = Admin::all();
        foreach ($admins as $admin) {
            $wallet = $walletService->getOrCreateStaffWallet($admin);
            echo "Created wallet for {$admin->name}: {$wallet->name}\n";
        }

        // Create default expense wallets
        echo "\nCreating default expense wallets...\n";

        $expenseWallets = [
            [
                'name' => 'General Expenses',
                'owner_id' => null,
            ],
            [
                'name' => 'Salaries & Wages',
                'owner_id' => null,
            ],
            [
                'name' => 'Rent & Utilities',
                'owner_id' => null,
            ],
            [
                'name' => 'Maintenance & Repairs',
                'owner_id' => null,
            ],
        ];

        foreach ($expenseWallets as $walletData) {
            $wallet = Wallet::firstOrCreate(
                [
                    'name' => $walletData['name'],
                    'type' => Wallet::TYPE_EXPENSE,
                ],
                [
                    'owner_type' => $walletData['owner_id'] ? Admin::class : null,
                    'owner_id' => $walletData['owner_id'],
                    'receivable_amount' => 0,
                    'payable_amount' => 0,
                ]
            );
            echo "Created expense wallet: {$wallet->name}\n";
        }

        echo "\nWallet seeding completed!\n";
    }
}
