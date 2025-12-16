<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $walletId = $request->get('wallet_id');
        $categoryId = $request->get('category_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Expense::with(['wallet', 'category', 'createdBy'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by wallet
        if ($walletId) {
            $query->forWallet($walletId);
        }

        // Filter by category
        if ($categoryId) {
            $query->inCategory($categoryId);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $expenses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ExpenseResource::collection($expenses),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        // Validate that wallet is an expense wallet
        $wallet = Wallet::find($validated['wallet_id']);
        if (!$wallet->isExpenseWallet()) {
            return response()->json([
                'success' => false,
                'message' => 'Expenses must be paid from an expense wallet.',
            ], 422);
        }

        // Check sufficient balance
        if ($wallet->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance in expense wallet.',
                'errors' => [
                    'amount' => ["Available balance: $" . number_format($wallet->balance, 2)],
                ],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $expense = Expense::create([
                ...$validated,
                'created_by_admin_id' => Auth::guard('admin')->id(),
            ]);

            // Update wallet payable amount
            $wallet->addPayable($validated['amount']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully.',
                'data' => new ExpenseResource($expense->load(['wallet', 'category', 'createdBy'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['wallet', 'category', 'createdBy', 'walletTransaction']);

        return response()->json([
            'success' => true,
            'data' => new ExpenseResource($expense),
        ]);
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'sometimes|required|exists:expense_categories,id',
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $expense->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data' => new ExpenseResource($expense->load(['wallet', 'category', 'createdBy'])),
        ]);
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Expenses cannot be deleted for audit purposes. Please contact administrator if correction is needed.',
        ], 403);
    }

    /**
     * Get summary statistics for expenses.
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $walletId = $request->get('wallet_id');

        $query = Expense::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($walletId) {
            $query->forWallet($walletId);
        }

        $total = $query->sum('amount');
        $count = $query->count();
        $avgExpense = $count > 0 ? $total / $count : 0;

        // Get expenses by category
        $byCategory = Expense::with('category')
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($walletId, fn($q) => $q->forWallet($walletId))
            ->get()
            ->groupBy('expense_category_id')
            ->map(function ($group) {
                return [
                    'category' => $group->first()->category->name,
                    'total' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_amount' => (float) $total,
                'total_count' => $count,
                'average_expense' => (float) $avgExpense,
                'by_category' => $byCategory,
            ],
        ]);
    }

    /**
     * Get expense wallets for dropdown.
     */
    public function getExpenseWallets(): JsonResponse
    {
        $wallets = Wallet::expense()
            ->orderBy('name')
            ->get()
            ->map(fn($wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'balance' => $wallet->balance,
            ]);

        return response()->json([
            'success' => true,
            'data' => $wallets,
        ]);
    }
}
