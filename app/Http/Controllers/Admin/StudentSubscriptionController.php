<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentSubscriptionRequest;
use App\Http\Requests\UpdateStudentSubscriptionRequest;
use App\Http\Requests\RenewStudentSubscriptionRequest;
use App\Http\Resources\StudentSubscriptionResource;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionOption;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\CouponLog;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentSubscriptionController extends Controller
{
    /**
     * Display a listing of student subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $studentId = $request->get('student_id');
        $programId = $request->get('program_id');
        $status = $request->get('status');
        $expiringSoon = $request->get('expiring_soon'); // boolean

        $query = StudentSubscription::with(['student', 'program', 'subscriptionOption'])
            ->orderBy('created_at', 'desc');

        // Filter by student
        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        // Filter by program
        if ($programId) {
            $query->where('program_id', $programId);
        }

        // Filter by status
        if ($status) {
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->expired();
            }
        }

        // Filter expiring soon
        if ($expiringSoon === 'true' || $expiringSoon === '1') {
            $query->expiringSoon();
        }

        $subscriptions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StudentSubscriptionResource::collection($subscriptions),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Store a newly created subscription in storage.
     */
    public function store(StoreStudentSubscriptionRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Get subscription option to calculate expiry date and price
            $subscriptionOption = SubscriptionOption::findOrFail($validated['subscription_option_id']);
            $basePrice = $validated['custom_price'] ?? $subscriptionOption->price;

            // Initialize discount variables
            $discountType = $validated['discount_type'] ?? null;
            $discountValue = $validated['discount_value'] ?? null;
            $couponCode = null;

            // Handle coupon if provided
            if (!empty($validated['coupon_code'])) {
                try {
                    $couponService = new CouponService();
                    $coupon = $couponService->validateCoupon($validated['coupon_code']);
                    $calculation = $couponService->calculateDiscount($coupon, $basePrice);

                    // Override discount values with coupon
                    $discountType = $calculation['discount_type'];
                    $discountValue = $calculation['discount_value'];
                    $couponCode = $coupon->code;

                    // Will apply coupon after subscription is created
                } catch (ValidationException $e) {
                    throw $e;
                }
            }

            $subscription = StudentSubscription::create([
                'student_id' => $validated['student_id'],
                'program_id' => $validated['program_id'],
                'subscription_option_id' => $validated['subscription_option_id'],
                'custom_price' => $validated['custom_price'] ?? null,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'coupon_code' => $couponCode,
                'start_date' => $validated['start_date'],
                'expiry_date' => now()->parse($validated['start_date'])->addMonths($subscriptionOption->duration_months),
                'status' => 'active',
            ]);

            // Apply coupon and create log if coupon was used
            if (!empty($validated['coupon_code']) && isset($coupon)) {
                $couponService->applyCoupon(
                    $coupon,
                    $validated['student_id'],
                    $basePrice,
                    CouponLog::PURPOSE_SUBSCRIPTION,
                    StudentSubscription::class,
                    $subscription->id,
                    "Applied to subscription #{$subscription->id}"
                );
            }

            // Update student's last subscription expiry
            $student = Student::findOrFail($validated['student_id']);
            $student->update([
                'last_subscription_expiry' => $subscription->expiry_date,
                'status' => Student::STATUS_ACTIVE,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully.',
                'data' => new StudentSubscriptionResource($subscription->load(['student', 'program', 'subscriptionOption'])),
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified subscription.
     */
    public function show(StudentSubscription $subscription): JsonResponse
    {
        $subscription->load(['student', 'program', 'subscriptionOption']);

        return response()->json([
            'success' => true,
            'data' => new StudentSubscriptionResource($subscription),
        ]);
    }

    /**
     * Update the specified subscription in storage.
     */
    public function update(UpdateStudentSubscriptionRequest $request, StudentSubscription $subscription): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // If subscription option changed, recalculate expiry date
            if (isset($validated['subscription_option_id']) && $validated['subscription_option_id'] !== $subscription->subscription_option_id) {
                $subscriptionOption = SubscriptionOption::findOrFail($validated['subscription_option_id']);
                $validated['expiry_date'] = now()->parse($validated['start_date'] ?? $subscription->start_date)
                    ->addMonths($subscriptionOption->duration_months);
            }

            $subscription->update($validated);

            // Update student's last subscription expiry if this is the latest subscription
            $latestSubscription = StudentSubscription::where('student_id', $subscription->student_id)
                ->orderBy('expiry_date', 'desc')
                ->first();

            if ($latestSubscription && $latestSubscription->id === $subscription->id) {
                $subscription->student->update([
                    'last_subscription_expiry' => $subscription->expiry_date,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully.',
                'data' => new StudentSubscriptionResource($subscription->load(['student', 'program', 'subscriptionOption'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified subscription from storage.
     */
    public function destroy(StudentSubscription $subscription): JsonResponse
    {
        DB::beginTransaction();

        try {
            $studentId = $subscription->student_id;
            $subscription->delete();

            // Update student's last subscription expiry
            $latestSubscription = StudentSubscription::where('student_id', $studentId)
                ->orderBy('expiry_date', 'desc')
                ->first();

            $student = Student::findOrFail($studentId);
            $student->update([
                'last_subscription_expiry' => $latestSubscription ? $latestSubscription->expiry_date : null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Renew the specified subscription.
     */
    public function renew(RenewStudentSubscriptionRequest $request, StudentSubscription $subscription): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Get duration from request or use subscription option default
            $durationMonths = $validated['duration_months'] ?? $subscription->subscriptionOption->duration_months;

            // Get renewal price from request or use the subscription's final price
            $renewalPrice = $validated['renewal_price'] ?? $subscription->final_price;

            // Renew the subscription
            $subscription->renew($durationMonths);

            // Update student's last subscription expiry and status
            $subscription->student->update([
                'last_subscription_expiry' => $subscription->expiry_date,
                'status' => Student::STATUS_ACTIVE,
            ]);

            // Create payment if requested
            $payment = null;
            if ($validated['create_payment'] ?? false) {
                // Create the payment record
                $payment = Payment::create([
                    'student_id' => $subscription->student_id,
                    'admin_id' => Auth::guard('admin')->id(),
                    'amount' => $renewalPrice,
                    'status' => $validated['payment_status'] ?? 'paid',
                    'payment_method' => $validated['payment_method'],
                    'note' => $validated['payment_note'] ?? "Renewal payment for {$subscription->subscriptionOption->name}",
                    'paid_at' => ($validated['payment_status'] ?? 'paid') === 'paid' ? now() : null,
                ]);

                // Create payment item
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'item_type' => 'subscription',
                    'item_id' => $subscription->id,
                    'description' => "Renewal: {$subscription->subscriptionOption->name} ({$durationMonths} months)",
                    'quantity' => 1,
                    'unit_price' => $renewalPrice,
                    'discount_value' => 0,
                    'total_price' => $renewalPrice,
                ]);
            }

            DB::commit();

            $responseData = [
                'success' => true,
                'message' => 'Subscription renewed successfully.',
                'data' => new StudentSubscriptionResource($subscription->load(['student', 'program', 'subscriptionOption'])),
            ];

            // Include payment data if payment was created
            if ($payment) {
                $responseData['payment'] = [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                ];
            }

            return response()->json($responseData);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to renew subscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark the specified subscription as expired.
     */
    public function expire(StudentSubscription $subscription): JsonResponse
    {
        DB::beginTransaction();

        try {
            $subscription->markAsExpired();

            // Check if student has any active subscriptions
            $hasActiveSubscription = StudentSubscription::where('student_id', $subscription->student_id)
                ->active()
                ->exists();

            if (!$hasActiveSubscription) {
                $subscription->student->update([
                    'status' => Student::STATUS_EXPIRED,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription marked as expired.',
                'data' => new StudentSubscriptionResource($subscription->load(['student', 'program', 'subscriptionOption'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to expire subscription.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscriptions expiring soon.
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $perPage = $request->get('per_page', 15);

        $subscriptions = StudentSubscription::with(['student', 'program', 'subscriptionOption'])
            ->expiringSoon($days)
            ->orderBy('expiry_date', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StudentSubscriptionResource::collection($subscriptions),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }
}
