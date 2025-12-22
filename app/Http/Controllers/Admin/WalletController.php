<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\TransactionLog;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get all wallets.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->get('type');

        $query = Wallet::with('owner')->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $wallets = $query->get();

        return response()->json([
            'success' => true,
            'data' => $wallets->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'type' => $wallet->type,
                    'owner' => $wallet->owner ? [
                        'id' => $wallet->owner->id,
                        'name' => $wallet->owner->name,
                        'email' => $wallet->owner->email,
                    ] : null,
                    'receivable_amount' => (float) $wallet->receivable_amount,
                    'payable_amount' => (float) $wallet->payable_amount,
                    'balance' => $wallet->balance,
                    'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Get wallet details with transactions.
     */
    public function show(int $id): JsonResponse
    {
        $wallet = Wallet::with(['owner', 'transactions.createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'owner' => $wallet->owner ? [
                    'id' => $wallet->owner->id,
                    'name' => $wallet->owner->name,
                    'email' => $wallet->owner->email,
                ] : null,
                'receivable_amount' => (float) $wallet->receivable_amount,
                'payable_amount' => (float) $wallet->payable_amount,
                'balance' => $wallet->balance,
                'transactions' => $wallet->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_type' => $transaction->transaction_type,
                        'amount' => (float) $transaction->amount,
                        'direction' => $transaction->direction,
                        'description' => $transaction->description,
                        'created_by' => $transaction->createdBy ? [
                            'id' => $transaction->createdBy->id,
                            'name' => $transaction->createdBy->name,
                        ] : null,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get wallet transactions.
     */
    public function transactions(Request $request, int $walletId): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $transactions = WalletTransaction::with('createdBy')
            ->where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                    'amount' => (float) $transaction->amount,
                    'direction' => $transaction->direction,
                    'description' => $transaction->description,
                    'created_by' => $transaction->createdBy ? [
                        'id' => $transaction->createdBy->id,
                        'name' => $transaction->createdBy->name,
                    ] : null,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Transfer money between wallets.
     */
    public function transfer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_wallet_id' => 'required|exists:wallets,id',
            'to_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $fromWallet = Wallet::findOrFail($request->from_wallet_id);
            $toWallet = Wallet::findOrFail($request->to_wallet_id);
            $admin = Auth::guard('admin')->user();

            // Determine transfer type and execute
            if ($fromWallet->isStaffWallet() && $toWallet->isMainCashbox()) {
                $result = $this->walletService->transferStaffToMainCashbox(
                    $fromWallet,
                    $request->amount,
                    $admin,
                    $request->note
                );
            } elseif ($fromWallet->isMainCashbox() && $toWallet->isExpenseWallet()) {
                $result = $this->walletService->transferMainCashboxToExpenseWallet(
                    $toWallet,
                    $request->amount,
                    $admin,
                    $request->note
                );
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transfer route. Allowed: Staff → Main Cashbox, Main Cashbox → Expense Wallet',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'outgoing_transaction_id' => $result['outgoing']->id,
                    'incoming_transaction_id' => $result['incoming']->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get balance summary for dashboard.
     */
    public function balanceSummary(): JsonResponse
    {
        $summary = $this->walletService->getBalanceSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get current admin's wallets.
     */
    public function myWallet(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        // Get all wallets owned by this admin
        $wallets = Wallet::with(['transactions' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }, 'transactions.createdBy'])
            ->where('owner_type', get_class($admin))
            ->where('owner_id', $admin->id)
            ->orderBy('type')
            ->get();

        // Calculate totals
        $totalBalance = 0;
        $totalReceivable = 0;
        $totalPayable = 0;

        $walletsData = $wallets->map(function ($wallet) use (&$totalBalance, &$totalReceivable, &$totalPayable) {
            $totalBalance += $wallet->balance;
            $totalReceivable += $wallet->receivable_amount;
            $totalPayable += $wallet->payable_amount;

            return [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'receivable_amount' => (float) $wallet->receivable_amount,
                'payable_amount' => (float) $wallet->payable_amount,
                'balance' => $wallet->balance,
                'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
                'recent_transactions' => $wallet->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_type' => $transaction->transaction_type,
                        'amount' => (float) $transaction->amount,
                        'direction' => $transaction->direction,
                        'description' => $transaction->description,
                        'created_by' => $transaction->createdBy ? [
                            'id' => $transaction->createdBy->id,
                            'name' => $transaction->createdBy->name,
                        ] : null,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'wallets' => $walletsData,
                'summary' => [
                    'total_balance' => $totalBalance,
                    'total_receivable' => $totalReceivable,
                    'total_payable' => $totalPayable,
                    'wallet_count' => $wallets->count(),
                ],
            ],
        ]);
    }

    /**
     * Create a new expense wallet.
     */
    public function createExpenseWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'owner_id' => 'nullable|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $owner = $request->owner_id ? \App\Models\Admin::find($request->owner_id) : null;
            $wallet = $this->walletService->createExpenseWallet($request->name, $owner);

            return response()->json([
                'success' => true,
                'message' => 'Expense wallet created successfully',
                'data' => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'type' => $wallet->type,
                    'balance' => $wallet->balance,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new wallet for a specific admin user (Administrator only).
     */
    public function createWalletForUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|exists:admins,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:staff,expense',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $targetAdmin = \App\Models\Admin::findOrFail($request->admin_id);
            $wallet = $this->walletService->createWalletForAdmin(
                $targetAdmin,
                $request->name,
                $request->type
            );

            return response()->json([
                'success' => true,
                'message' => 'Wallet created successfully',
                'data' => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'type' => $wallet->type,
                    'owner' => [
                        'id' => $targetAdmin->id,
                        'name' => $targetAdmin->name,
                        'email' => $targetAdmin->email,
                    ],
                    'balance' => $wallet->balance,
                    'receivable_amount' => (float) $wallet->receivable_amount,
                    'payable_amount' => (float) $wallet->payable_amount,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all admins for wallet assignment (Administrator only).
     */
    public function getAdminsForWalletAssignment(): JsonResponse
    {
        $admins = \App\Models\Admin::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * Get wallets for a specific user.
     */
    public function getUserWallets(int $adminId): JsonResponse
    {
        $targetAdmin = \App\Models\Admin::findOrFail($adminId);

        $wallets = Wallet::where('owner_type', \App\Models\Admin::class)
            ->where('owner_id', $targetAdmin->id)
            ->orderBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wallets->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'type' => $wallet->type,
                    'balance' => $wallet->balance,
                    'receivable_amount' => (float) $wallet->receivable_amount,
                    'payable_amount' => (float) $wallet->payable_amount,
                ];
            }),
        ]);
    }

    /**
     * Update wallet balance (Admin only) - Creates adjustment transactions.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'receivable_amount' => 'required|numeric|min:0',
            'payable_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wallet = Wallet::findOrFail($id);
            $admin = Auth::guard('admin')->user();

            // Update wallet name
            $wallet->name = $request->name;
            $wallet->save();

            $oldReceivable = (float) $wallet->receivable_amount;
            $oldPayable = (float) $wallet->payable_amount;
            $oldBalance = $wallet->balance;

            $newReceivable = (float) $request->receivable_amount;
            $newPayable = (float) $request->payable_amount;

            // Calculate differences
            $receivableDiff = $newReceivable - $oldReceivable;
            $payableDiff = $newPayable - $oldPayable;

            // Update receivable if changed
            if ($receivableDiff != 0) {
                if ($receivableDiff > 0) {
                    $wallet->addReceivable(abs($receivableDiff));
                    $transactionType = 'in';
                    $description = sprintf(
                        'Receivable adjustment: +$%s (Admin: %s)',
                        number_format(abs($receivableDiff), 2),
                        $admin->name
                    );
                } else {
                    $wallet->subtractReceivable(abs($receivableDiff));
                    $transactionType = 'out';
                    $description = sprintf(
                        'Receivable adjustment: -$%s (Admin: %s)',
                        number_format(abs($receivableDiff), 2),
                        $admin->name
                    );
                }

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'transaction_type' => 'adjustment',
                    'amount' => abs($receivableDiff),
                    'direction' => $transactionType,
                    'description' => $description,
                    'created_by_type' => get_class($admin),
                    'created_by_id' => $admin->id,
                ]);
            }

            // Update payable if changed
            if ($payableDiff != 0) {
                if ($payableDiff > 0) {
                    $wallet->addPayable(abs($payableDiff));
                    $transactionType = 'out';
                    $description = sprintf(
                        'Payable adjustment: +$%s (Admin: %s)',
                        number_format(abs($payableDiff), 2),
                        $admin->name
                    );
                } else {
                    $wallet->subtractPayable(abs($payableDiff));
                    $transactionType = 'in';
                    $description = sprintf(
                        'Payable adjustment: -$%s (Admin: %s)',
                        number_format(abs($payableDiff), 2),
                        $admin->name
                    );
                }

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'transaction_type' => 'adjustment',
                    'amount' => abs($payableDiff),
                    'direction' => $transactionType,
                    'description' => $description,
                    'created_by_type' => get_class($admin),
                    'created_by_id' => $admin->id,
                ]);
            }

            $wallet->refresh();
            $newBalance = $wallet->balance;

            // Create transaction log for the overall adjustment
            TransactionLog::create([
                'admin_id' => $admin->id,
                'transaction_type' => 'wallet_adjustment',
                'amount' => abs($newBalance - $oldBalance),
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'description' => sprintf(
                    'Wallet balance adjustment for %s. Receivable: $%s → $%s, Payable: $%s → $%s',
                    $wallet->name,
                    number_format($oldReceivable, 2),
                    number_format($newReceivable, 2),
                    number_format($oldPayable, 2),
                    number_format($newPayable, 2)
                ),
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'old_receivable' => $oldReceivable,
                    'new_receivable' => $newReceivable,
                    'old_payable' => $oldPayable,
                    'new_payable' => $newPayable,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet updated successfully',
                'data' => [
                    'id' => $wallet->id,
                    'receivable_amount' => (float) $wallet->receivable_amount,
                    'payable_amount' => (float) $wallet->payable_amount,
                    'balance' => $wallet->balance,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
