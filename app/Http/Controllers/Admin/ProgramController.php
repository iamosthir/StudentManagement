<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use App\Models\SubscriptionOption;
use App\Models\Product;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Display a listing of programs.
     */
    public function index(Request $request)
    {
        $query = Program::query()->with(['subscriptionOptions', 'products']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Order by
        $query->orderBy($request->get('sort_by', 'name'), $request->get('sort_order', 'asc'));

        $programs = $query->paginate($request->get('per_page', 15));

        return ProgramResource::collection($programs);
    }

    /**
     * Store a newly created program.
     */
    public function store(StoreProgramRequest $request)
    {
        $program = Program::create($request->validated());

        // Attach subscription options if provided
        if ($request->has('subscription_option_ids')) {
            $program->subscriptionOptions()->sync($request->subscription_option_ids);
        }

        // Attach products if provided
        if ($request->has('product_ids')) {
            $program->products()->sync($request->product_ids);
        }

        $program->load(['subscriptionOptions', 'products']);

        return response()->json([
            'success' => true,
            'message' => 'Program created successfully',
            'data' => new ProgramResource($program)
        ], 201);
    }

    /**
     * Display the specified program.
     */
    public function show(Program $program)
    {
        $program->load(['subscriptionOptions', 'products']);

        return response()->json([
            'success' => true,
            'data' => new ProgramResource($program)
        ]);
    }

    /**
     * Update the specified program.
     */
    public function update(UpdateProgramRequest $request, Program $program)
    {
        $program->update($request->validated());

        // Sync subscription options if provided
        if ($request->has('subscription_option_ids')) {
            $program->subscriptionOptions()->sync($request->subscription_option_ids);
        }

        // Sync products if provided
        if ($request->has('product_ids')) {
            $program->products()->sync($request->product_ids);
        }

        $program->load(['subscriptionOptions', 'products']);

        return response()->json([
            'success' => true,
            'message' => 'Program updated successfully',
            'data' => new ProgramResource($program)
        ]);
    }

    /**
     * Remove the specified program.
     */
    public function destroy(Program $program)
    {
        // Check if program has active subscriptions
        $activeSubscriptions = $program->subscriptionOptions()
            ->whereHas('studentSubscriptions', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete program with active student subscriptions'
            ], 422);
        }

        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program deleted successfully'
        ]);
    }

    /**
     * Get all available subscription options for assignment.
     */
    public function getSubscriptionOptions()
    {
        $options = SubscriptionOption::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    /**
     * Get all available products for assignment.
     */
    public function getProducts()
    {
        $products = Product::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
