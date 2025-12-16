<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionLogResource;
use App\Models\TransactionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionLogController extends Controller
{
    /**
     * Display a listing of transaction logs.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $type = $request->get('type');
        $paymentId = $request->get('payment_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $adminId = $request->get('admin_id');

        $query = TransactionLog::with(['admin', 'payment'])
            ->orderBy('created_at', 'desc');

        // Filter by transaction type
        if ($type) {
            $query->where('transaction_type', $type);
        }

        // Filter by payment ID
        if ($paymentId) {
            $query->where('payment_id', $paymentId);
        }

        // Filter by admin ID
        if ($adminId) {
            $query->where('admin_id', $adminId);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TransactionLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get summary statistics for transaction logs.
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = TransactionLog::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $totalPayments = (clone $query)->where('transaction_type', TransactionLog::TYPE_PAYMENT)->sum('amount');
        $totalTransfersIn = (clone $query)->where('transaction_type', TransactionLog::TYPE_TRANSFER_IN)->sum('amount');
        $totalTransfersOut = (clone $query)->where('transaction_type', TransactionLog::TYPE_TRANSFER_OUT)->sum('amount');

        $paymentsCount = (clone $query)->where('transaction_type', TransactionLog::TYPE_PAYMENT)->count();
        $transfersInCount = (clone $query)->where('transaction_type', TransactionLog::TYPE_TRANSFER_IN)->count();
        $transfersOutCount = (clone $query)->where('transaction_type', TransactionLog::TYPE_TRANSFER_OUT)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'payments' => (float) $totalPayments,
                    'transfers_in' => (float) $totalTransfersIn,
                    'transfers_out' => (float) $totalTransfersOut,
                ],
                'counts' => [
                    'payments' => $paymentsCount,
                    'transfers_in' => $transfersInCount,
                    'transfers_out' => $transfersOutCount,
                ],
            ],
        ]);
    }

    /**
     * Display the specified transaction log.
     */
    public function show(TransactionLog $log): JsonResponse
    {
        $log->load(['admin', 'payment']);

        return response()->json([
            'success' => true,
            'data' => new TransactionLogResource($log),
        ]);
    }
}
