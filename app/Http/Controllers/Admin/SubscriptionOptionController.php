<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionOptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionOptionRequest;
use App\Http\Resources\SubscriptionOptionResource;
use App\Models\SubscriptionOption;
use Illuminate\Http\Request;

class SubscriptionOptionController extends Controller
{
    /**
     * Display a listing of subscription options.
     */
    public function index(Request $request)
    {
        $query = SubscriptionOption::query()->with('programs');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by payment type
        if ($request->has('payment_type')) {
            if ($request->payment_type === 'full') {
                $query->where('is_full_payment', true);
            } elseif ($request->payment_type === 'installment') {
                $query->where('is_full_payment', false);
            }
        }

        // Order by
        $query->orderBy($request->get('sort_by', 'name'), $request->get('sort_order', 'asc'));

        $options = $query->paginate($request->get('per_page', 15));

        return SubscriptionOptionResource::collection($options);
    }

    /**
     * Store a newly created subscription option.
     */
    public function store(StoreSubscriptionOptionRequest $request)
    {
        $option = SubscriptionOption::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subscription option created successfully',
            'data' => new SubscriptionOptionResource($option)
        ], 201);
    }

    /**
     * Display the specified subscription option.
     */
    public function show(SubscriptionOption $subscriptionOption)
    {
        $subscriptionOption->load('programs');

        return response()->json([
            'success' => true,
            'data' => new SubscriptionOptionResource($subscriptionOption)
        ]);
    }

    /**
     * Update the specified subscription option.
     */
    public function update(UpdateSubscriptionOptionRequest $request, SubscriptionOption $subscriptionOption)
    {
        $subscriptionOption->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subscription option updated successfully',
            'data' => new SubscriptionOptionResource($subscriptionOption)
        ]);
    }

    /**
     * Remove the specified subscription option.
     */
    public function destroy(SubscriptionOption $subscriptionOption)
    {
        // Check if option is used in any active student subscriptions
        $activeUsage = $subscriptionOption->programs()
            ->whereHas('studentSubscriptions', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        if ($activeUsage > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete subscription option that is being used by active student subscriptions'
            ], 422);
        }

        $subscriptionOption->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription option deleted successfully'
        ]);
    }
}
