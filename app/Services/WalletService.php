<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\TransactionLog;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Get or create a staff wallet for an admin.
     */
    public function getOrCreateStaffWallet(Admin $admin): Wallet
    {
        return Wallet::firstOrCreate(
            [
                'owner_type' => Admin::class,
                'owner_id' => $admin->id,
                'type' => Wallet::TYPE_STAFF,
            ],
            [
                'name' => "{$admin->name}'s Wallet",
                'receivable_amount' => 0,
                'payable_amount' => 0,
            ]
        );
    }

    /**
     * Get or create the main cashbox wallet.
     */
    public function getOrCreateMainCashbox(): Wallet
    {
        return Wallet::firstOrCreate(
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
    }

    /**
     * Add a payment to a staff wallet.
     */
    public function addPaymentToStaffWallet(
        Admin $admin,
        float $amount,
        int $paymentId,
        string $description = 'Student payment received'
    ): WalletTransaction {
        DB::beginTransaction();

        try {
            $wallet = $this->getOrCreateStaffWallet($admin);

            // Get balance before transaction
            $balanceBefore = $wallet->balance;

            // Update wallet receivable amount
            $wallet->addReceivable($amount);
            $wallet->refresh();

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'related_payment_id' => $paymentId,
                'transaction_type' => WalletTransaction::TYPE_PAYMENT_IN,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_IN,
                'description' => $description,
                'created_by_admin_id' => $admin->id,
            ]);

            // Create transaction log
            TransactionLog::create([
                'admin_id' => $admin->id,
                'payment_id' => $paymentId,
                'transaction_type' => TransactionLog::TYPE_PAYMENT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'metadata' => [
                    'payment_id' => $paymentId,
                    'wallet_id' => $wallet->id,
                ],
            ]);

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transfer money from staff wallet to main cashbox.
     */
    public function transferStaffToMainCashbox(
        Wallet $staffWallet,
        float $amount,
        Admin $admin,
        string $note = null
    ): array {
        DB::beginTransaction();

        try {
            // Validate staff wallet
            if (!$staffWallet->isStaffWallet()) {
                throw new Exception('Source wallet must be a staff wallet');
            }

            // Check sufficient balance
            if ($staffWallet->balance < $amount) {
                throw new Exception('Insufficient balance in staff wallet');
            }

            // Get or create main cashbox
            $mainCashbox = $this->getOrCreateMainCashbox();

            $description = $note ?? "Transfer from {$staffWallet->name} to Main Cashbox";

            // Create outgoing transaction for staff wallet
            $outgoingTransaction = WalletTransaction::create([
                'wallet_id' => $staffWallet->id,
                'transaction_type' => WalletTransaction::TYPE_TRANSFER_OUT,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_OUT,
                'description' => $description,
                'created_by_admin_id' => $admin->id,
            ]);

            // Create incoming transaction for main cashbox
            $incomingTransaction = WalletTransaction::create([
                'wallet_id' => $mainCashbox->id,
                'transaction_type' => WalletTransaction::TYPE_TRANSFER_IN,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_IN,
                'description' => $description,
                'created_by_admin_id' => $admin->id,
            ]);

            DB::commit();

            return [
                'outgoing' => $outgoingTransaction,
                'incoming' => $incomingTransaction,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transfer money from main cashbox to expense wallet.
     */
    public function transferMainCashboxToExpenseWallet(
        Wallet $expenseWallet,
        float $amount,
        Admin $admin,
        string $note = null
    ): array {
        DB::beginTransaction();

        try {
            // Validate expense wallet
            if (!$expenseWallet->isExpenseWallet()) {
                throw new Exception('Destination wallet must be an expense wallet');
            }

            // Get main cashbox
            $mainCashbox = $this->getOrCreateMainCashbox();

            // Check sufficient balance
            if ($mainCashbox->balance < $amount) {
                throw new Exception('Insufficient balance in main cashbox');
            }

            $description = $note ?? "Transfer from Main Cashbox to {$expenseWallet->name}";

            // Create outgoing transaction for main cashbox
            $outgoingTransaction = WalletTransaction::create([
                'wallet_id' => $mainCashbox->id,
                'transaction_type' => WalletTransaction::TYPE_TRANSFER_OUT,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_OUT,
                'description' => $description,
                'created_by_admin_id' => $admin->id,
            ]);

            // Create incoming transaction for expense wallet
            $incomingTransaction = WalletTransaction::create([
                'wallet_id' => $expenseWallet->id,
                'transaction_type' => WalletTransaction::TYPE_TRANSFER_IN,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_IN,
                'description' => $description,
                'created_by_admin_id' => $admin->id,
            ]);

            DB::commit();

            return [
                'outgoing' => $outgoingTransaction,
                'incoming' => $incomingTransaction,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deduct money from expense wallet for an expense.
     */
    public function deductFromExpenseWallet(
        Wallet $expenseWallet,
        float $amount,
        int $expenseId,
        Admin $admin,
        string $description
    ): WalletTransaction {
        DB::beginTransaction();

        try {
            // Validate expense wallet
            if (!$expenseWallet->isExpenseWallet()) {
                throw new Exception('Wallet must be an expense wallet');
            }

            // Check sufficient balance
            if ($expenseWallet->balance < $amount) {
                throw new Exception('Insufficient balance in expense wallet');
            }

            $transaction = WalletTransaction::create([
                'wallet_id' => $expenseWallet->id,
                'related_expense_id' => $expenseId,
                'transaction_type' => WalletTransaction::TYPE_EXPENSE,
                'amount' => $amount,
                'direction' => WalletTransaction::DIRECTION_OUT,
                'description' => $description,
                'created_by_admin_id' => $admin->id,
            ]);

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get wallet balance summary for dashboard.
     */
    public function getBalanceSummary(): array
    {
        $staffWallets = Wallet::staff()->get();
        $mainCashbox = $this->getOrCreateMainCashbox();
        $expenseWallets = Wallet::expense()->get();

        return [
            'staff_total' => $staffWallets->sum('receivable_amount') - $staffWallets->sum('payable_amount'),
            'main_cashbox' => $mainCashbox->balance,
            'expense_total' => $expenseWallets->sum('receivable_amount') - $expenseWallets->sum('payable_amount'),
            'grand_total' => ($staffWallets->sum('receivable_amount') - $staffWallets->sum('payable_amount')) +
                            $mainCashbox->balance +
                            ($expenseWallets->sum('receivable_amount') - $expenseWallets->sum('payable_amount')),
        ];
    }

    /**
     * Create or get an expense wallet.
     */
    public function createExpenseWallet(
        string $name,
        Admin $owner = null
    ): Wallet {
        return Wallet::create([
            'owner_type' => $owner ? Admin::class : null,
            'owner_id' => $owner?->id,
            'name' => $name,
            'type' => Wallet::TYPE_EXPENSE,
            'receivable_amount' => 0,
            'payable_amount' => 0,
        ]);
    }

    /**
     * Create a new staff wallet for an admin (allows multiple staff wallets).
     */
    public function createStaffWallet(
        Admin $admin,
        string $name = null
    ): Wallet {
        $walletName = $name ?? "{$admin->name}'s Wallet";

        return Wallet::create([
            'owner_type' => Admin::class,
            'owner_id' => $admin->id,
            'name' => $walletName,
            'type' => Wallet::TYPE_STAFF,
            'receivable_amount' => 0,
            'payable_amount' => 0,
        ]);
    }

    /**
     * Create a wallet for a specific admin (staff or expense type).
     */
    public function createWalletForAdmin(
        Admin $admin,
        string $name,
        string $type
    ): Wallet {
        // Validate wallet type
        $allowedTypes = [Wallet::TYPE_STAFF, Wallet::TYPE_EXPENSE];
        if (!in_array($type, $allowedTypes)) {
            throw new Exception('Invalid wallet type. Allowed types: staff, expense');
        }

        return Wallet::create([
            'owner_type' => Admin::class,
            'owner_id' => $admin->id,
            'name' => $name,
            'type' => $type,
            'receivable_amount' => 0,
            'payable_amount' => 0,
        ]);
    }
}
