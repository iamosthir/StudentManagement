<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessWalletTransferRequest;
use App\Http\Requests\Admin\StoreWalletTransferRequest;
use App\Http\Resources\WalletTransferRequestResource;
use App\Models\Admin;
use App\Models\TransactionLog;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WalletTransferController extends Controller
{
    /**
     * Display a listing of wallet transfer requests.
     */
    public function index(): JsonResponse
    {
        $currentAdmin = auth('admin')->user();

        $query = WalletTransferRequest::with(['fromAdmin', 'toAdmin', 'processedByAdmin']);

        // If not Administrator, only show user's own transfers
        if (!$currentAdmin->hasRole('Administrator')) {
            $query->where(function($q) use ($currentAdmin) {
                $q->where('from_admin_id', $currentAdmin->id)
                  ->orWhere('to_admin_id', $currentAdmin->id);
            });
        }

        $transfers = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'transfers' => WalletTransferRequestResource::collection($transfers->items()),
            'pagination' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    /**
     * Get list of admins for transfer dropdown.
     */
    public function getAdmins(): JsonResponse
    {
        $currentAdmin = auth('admin')->user();

        $admins = Admin::where('id', '!=', $currentAdmin->id)
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($admin) {
                $primaryWallet = $admin->primaryWallet();
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'balance' => $primaryWallet ? $primaryWallet->balance : 0,
                ];
            });

        return response()->json(['admins' => $admins]);
    }

    /**
     * Store a new transfer request.
     */
    public function store(StoreWalletTransferRequest $request): JsonResponse
    {
        $fromAdmin = auth('admin')->user();
        $toAdmin = Admin::findOrFail($request->to_admin_id);

        // Get primary wallet and validate sufficient balance
        $fromWallet = $fromAdmin->primaryWallet();
        $currentBalance = $fromWallet ? $fromWallet->balance : 0;

        if ($currentBalance <= 0) {
            return response()->json([
                'message' => 'Your balance is insufficient.',
                'errors' => [
                    'amount' => ['Your balance is insufficient.']
                ]
            ], 422);
        }

        if ($currentBalance < $request->amount) {
            return response()->json([
                'message' => 'Insufficient balance.',
                'errors' => [
                    'amount' => ["Your balance is insufficient. Available balance: $" . number_format($currentBalance, 2)]
                ]
            ], 422);
        }

        // Check if sender is Super Admin (has Administrator role)
        $isSuperAdmin = $fromAdmin->hasRole('Administrator');

        try {
            DB::beginTransaction();

            if ($isSuperAdmin) {
                // Super Admin can transfer directly without approval
                $this->executeTransfer($fromAdmin, $toAdmin, $request->amount, $request->notes);

                // Create a record with accepted status for history
                $transferRequest = WalletTransferRequest::create([
                    'from_admin_id' => $fromAdmin->id,
                    'to_admin_id' => $toAdmin->id,
                    'amount' => $request->amount,
                    'status' => 'accepted',
                    'notes' => $request->notes,
                    'processed_by_admin_id' => $fromAdmin->id,
                    'processed_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Transfer completed successfully.',
                    'transfer' => new WalletTransferRequestResource($transferRequest->load(['fromAdmin', 'toAdmin', 'processedByAdmin'])),
                ]);
            } else {
                // Regular admin creates pending transfer request
                $transferRequest = WalletTransferRequest::create([
                    'from_admin_id' => $fromAdmin->id,
                    'to_admin_id' => $toAdmin->id,
                    'amount' => $request->amount,
                    'status' => 'pending',
                    'notes' => $request->notes,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Transfer request created and waiting for approval.',
                    'transfer' => new WalletTransferRequestResource($transferRequest->load(['fromAdmin', 'toAdmin'])),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Transfer failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified transfer request.
     */
    public function show(WalletTransferRequest $transfer): JsonResponse
    {
        return response()->json([
            'transfer' => new WalletTransferRequestResource($transfer->load(['fromAdmin', 'toAdmin', 'processedByAdmin'])),
        ]);
    }

    /**
     * Process a transfer request (accept or reject).
     */
    public function process(ProcessWalletTransferRequest $request, WalletTransferRequest $transfer): JsonResponse
    {
        $processingAdmin = auth('admin')->user();

        // Check if transfer is already processed
        if ($transfer->status !== 'pending') {
            return response()->json([
                'message' => 'This transfer request has already been processed.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            if ($request->action === 'accept') {
                // Check if sender has sufficient balance
                $fromAdmin = $transfer->fromAdmin;
                $fromWallet = $fromAdmin->primaryWallet();
                $currentBalance = $fromWallet ? $fromWallet->balance : 0;

                if ($currentBalance < $transfer->amount) {
                    // Auto-cancel due to insufficient balance
                    $transfer->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => 'Low balance on account',
                        'processed_by_admin_id' => $processingAdmin->id,
                        'processed_at' => now(),
                    ]);

                    DB::commit();

                    return response()->json([
                        'message' => 'Transfer request cancelled due to insufficient balance.',
                        'transfer' => new WalletTransferRequestResource($transfer->load(['fromAdmin', 'toAdmin', 'processedByAdmin'])),
                    ]);
                }

                // Execute the transfer
                $this->executeTransfer($fromAdmin, $transfer->toAdmin, $transfer->amount, $transfer->notes);

                // Update transfer request status
                $transfer->update([
                    'status' => 'accepted',
                    'processed_by_admin_id' => $processingAdmin->id,
                    'processed_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Transfer request accepted and completed successfully.',
                    'transfer' => new WalletTransferRequestResource($transfer->load(['fromAdmin', 'toAdmin', 'processedByAdmin'])),
                ]);
            } else {
                // Reject the transfer
                $transfer->update([
                    'status' => 'rejected',
                    'cancellation_reason' => $request->rejection_reason,
                    'processed_by_admin_id' => $processingAdmin->id,
                    'processed_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Transfer request rejected.',
                    'transfer' => new WalletTransferRequestResource($transfer->load(['fromAdmin', 'toAdmin', 'processedByAdmin'])),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute the actual transfer between two admins.
     */
    private function executeTransfer(Admin $fromAdmin, Admin $toAdmin, float $amount, ?string $notes): void
    {
        // Get or create wallets for both admins
        $fromWallet = $fromAdmin->primaryWallet();
        if (!$fromWallet) {
            $fromWallet = Wallet::create([
                'owner_type' => Admin::class,
                'owner_id' => $fromAdmin->id,
                'name' => $fromAdmin->name . ' Wallet',
                'type' => Wallet::TYPE_STAFF,
                'receivable_amount' => 0,
                'payable_amount' => 0,
            ]);
        }

        $toWallet = $toAdmin->primaryWallet();
        if (!$toWallet) {
            $toWallet = Wallet::create([
                'owner_type' => Admin::class,
                'owner_id' => $toAdmin->id,
                'name' => $toAdmin->name . ' Wallet',
                'type' => Wallet::TYPE_STAFF,
                'receivable_amount' => 0,
                'payable_amount' => 0,
            ]);
        }

        // Get balance before transaction
        $fromBalanceBefore = $fromWallet->balance;
        $toBalanceBefore = $toWallet->balance;

        // Update wallet balances
        // Sender: increase payable amount (money going out)
        $fromWallet->addPayable($amount);

        // Receiver: increase receivable amount (money coming in)
        $toWallet->addReceivable($amount);

        // Refresh to get updated balances
        $fromWallet->refresh();
        $toWallet->refresh();

        // Create outgoing transaction for sender
        WalletTransaction::create([
            'wallet_id' => $fromWallet->id,
            'transaction_type' => WalletTransaction::TYPE_TRANSFER_OUT,
            'amount' => $amount,
            'direction' => WalletTransaction::DIRECTION_OUT,
            'description' => $notes ?? "Transfer to {$toAdmin->name}",
            'created_by_admin_id' => auth('admin')->id(),
        ]);

        // Create incoming transaction for receiver
        WalletTransaction::create([
            'wallet_id' => $toWallet->id,
            'transaction_type' => WalletTransaction::TYPE_TRANSFER_IN,
            'amount' => $amount,
            'direction' => WalletTransaction::DIRECTION_IN,
            'description' => $notes ?? "Transfer from {$fromAdmin->name}",
            'created_by_admin_id' => auth('admin')->id(),
        ]);

        // Create transaction log for sender
        TransactionLog::create([
            'admin_id' => $fromAdmin->id,
            'payment_id' => null,
            'transaction_type' => TransactionLog::TYPE_TRANSFER_OUT,
            'amount' => $amount,
            'balance_before' => $fromBalanceBefore,
            'balance_after' => $fromWallet->balance,
            'description' => $notes ?? "Transfer to {$toAdmin->name}",
            'metadata' => [
                'to_admin_id' => $toAdmin->id,
                'to_admin_name' => $toAdmin->name,
            ],
        ]);

        // Create transaction log for receiver
        TransactionLog::create([
            'admin_id' => $toAdmin->id,
            'payment_id' => null,
            'transaction_type' => TransactionLog::TYPE_TRANSFER_IN,
            'amount' => $amount,
            'balance_before' => $toBalanceBefore,
            'balance_after' => $toWallet->balance,
            'description' => $notes ?? "Transfer from {$fromAdmin->name}",
            'metadata' => [
                'from_admin_id' => $fromAdmin->id,
                'from_admin_name' => $fromAdmin->name,
            ],
        ]);
    }

    /**
     * Get pending transfer requests.
     */
    public function pending(): JsonResponse
    {
        $transfers = WalletTransferRequest::with(['fromAdmin', 'toAdmin'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'transfers' => WalletTransferRequestResource::collection($transfers),
        ]);
    }

    /**
     * Get transaction logs (Administrator only).
     */
    public function transactionLogs(): JsonResponse
    {
        $logs = \App\Models\TransactionLog::with(['admin', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
