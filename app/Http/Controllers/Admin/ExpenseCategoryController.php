<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of expense categories.
     */
    public function index(): JsonResponse
    {
        $categories = ExpenseCategory::withCount('expenses')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ExpenseCategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created expense category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
        ]);

        $category = ExpenseCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data' => new ExpenseCategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified expense category.
     */
    public function show(ExpenseCategory $category): JsonResponse
    {
        $category->loadCount('expenses');

        return response()->json([
            'success' => true,
            'data' => new ExpenseCategoryResource($category),
        ]);
    }

    /**
     * Update the specified expense category.
     */
    public function update(Request $request, ExpenseCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data' => new ExpenseCategoryResource($category),
        ]);
    }

    /**
     * Remove the specified expense category.
     */
    public function destroy(ExpenseCategory $category): JsonResponse
    {
        // Check if category has expenses
        if ($category->expenses()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing expenses.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ]);
    }
}
