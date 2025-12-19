<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display a listing of coupons.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status'); // 'used' or 'unused'
        $search = $request->get('search');

        $query = Coupon::with(['student', 'admin'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status === 'used') {
            $query->used();
        } elseif ($status === 'unused') {
            $query->unused();
        }

        // Search by code or coupon name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('coupon_name', 'like', "%{$search}%");
            });
        }

        $coupons = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CouponResource::collection($coupons),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(StoreCouponRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Generate unique code if not provided
            if (!isset($validated['code'])) {
                $validated['code'] = Coupon::generateUniqueCode();
            }

            $coupon = Coupon::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Coupon created successfully.',
                'data' => new CouponResource($coupon->load(['student', 'admin'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create coupon.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified coupon.
     */
    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->load(['student', 'admin']);

        return response()->json([
            'success' => true,
            'data' => new CouponResource($coupon),
        ]);
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            $validated = $request->validated();

            $coupon->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Coupon updated successfully.',
                'data' => new CouponResource($coupon->load(['student', 'admin'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update coupon.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            // Prevent deletion of used coupons
            if ($coupon->is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a used coupon.',
                ], 422);
            }

            $coupon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coupon deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete coupon.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a new unique coupon code.
     */
    public function generateCode(): JsonResponse
    {
        try {
            $code = Coupon::generateUniqueCode();

            return response()->json([
                'success' => true,
                'code' => $code,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate coupon code.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify if a coupon code exists and is available.
     */
    public function verify(Request $request): JsonResponse
    {
        $code = $request->input('code');
        $amount = $request->input('amount');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code is required.',
            ], 422);
        }

        if (!$amount || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Valid amount is required.',
            ], 422);
        }

        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code not found.',
                'available' => false,
            ], 404);
        }

        if ($coupon->is_used) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon has already been used.',
                'available' => false,
            ], 422);
        }

        // Calculate discount using CouponService
        $couponService = app(\App\Services\CouponService::class);
        $calculation = $couponService->calculateDiscount($coupon, floatval($amount));

        return response()->json([
            'success' => true,
            'message' => 'Coupon is valid and available.',
            'available' => true,
            'data' => array_merge($calculation, [
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'coupon_name' => $coupon->coupon_name,
            ]),
        ]);
    }

    /**
     * Get coupon statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalCoupons = Coupon::count();
        $usedCoupons = Coupon::used()->count();
        $unusedCoupons = Coupon::unused()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalCoupons,
                'used' => $usedCoupons,
                'unused' => $unusedCoupons,
                'usage_percentage' => $totalCoupons > 0 ? round(($usedCoupons / $totalCoupons) * 100, 2) : 0,
            ],
        ]);
    }

    /**
     * Get students who have used coupons.
     */
    public function studentsWithCoupons(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Coupon::with(['student', 'admin'])
            ->where('is_used', true)
            ->orderBy('used_at', 'desc');

        // Search by student name, admission number, or coupon code/name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('full_name', 'like', "%{$search}%")
                               ->orWhere('admission_number', 'like', "%{$search}%");
                })
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('coupon_name', 'like', "%{$search}%");
            });
        }

        $coupons = $query->paginate($perPage);

        // Calculate discount amounts for each coupon
        $coupons->getCollection()->transform(function ($coupon) {
            $discountAmount = 0;

            if ($coupon->discount_type === 'percent') {
                // We need to find the payment or subscription to calculate the actual discount
                // For now, we'll show the discount value as is
                $discountAmount = $coupon->discount_value . '%';
            } else {
                $discountAmount = '$' . number_format($coupon->discount_value, 2);
            }

            $coupon->discount_amount_display = $discountAmount;
            return $coupon;
        });

        return response()->json([
            'success' => true,
            'data' => $coupons->getCollection()->map(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'student' => $coupon->student ? [
                        'id' => $coupon->student->id,
                        'admission_number' => $coupon->student->admission_number,
                        'full_name' => $coupon->student->full_name,
                    ] : null,
                    'admin' => $coupon->admin ? [
                        'id' => $coupon->admin->id,
                        'name' => $coupon->admin->name,
                    ] : null,
                    'coupon_code' => $coupon->code,
                    'coupon_name' => $coupon->coupon_name,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'discount_amount_display' => $coupon->discount_amount_display,
                    'used_at' => $coupon->used_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }
}
