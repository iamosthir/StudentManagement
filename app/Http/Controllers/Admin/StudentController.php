<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionOption;
use App\Models\CouponLog;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $status = $request->get('status');
        $programId = $request->get('program_id');

        $query = Student::with(['program', 'subscriptions.subscriptionOption', 'subscriptions.program'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Program filter
        if ($programId) {
            $query->where('program_id', $programId);
        }

        $students = $query->paginate($perPage);

        // Calculate financial statistics for each student
        $students->getCollection()->transform(function ($student) {
            // Calculate total payable: sum of all subscription final prices
            $totalSubscriptionCost = $student->subscriptions->sum('final_price');

            // Calculate total product cost: sum of all product payment items
            $totalProductCost = \DB::table('payment_items')
                ->join('payments', 'payment_items.payment_id', '=', 'payments.id')
                ->where('payments.student_id', $student->id)
                ->where('payment_items.item_type', 'product')
                ->sum(\DB::raw('payment_items.unit_price * payment_items.quantity'));

            // Total payable = subscriptions + products
            $totalPayable = $totalSubscriptionCost + $totalProductCost;

            // Calculate total paid amount
            $totalPaidAmount = $student->payments()->where('status', 'paid')->sum('amount');

            // Calculate total due
            $totalDueAmount = $totalPayable - $totalPaidAmount;

            // Add financial stats to student instance
            $student->total_subscription_cost = $totalPayable; // This is actually total payable now
            $student->total_paid_amount = $totalPaidAmount;
            $student->total_due_amount = max(0, $totalDueAmount);

            return $student;
        });

        return response()->json([
            'success' => true,
            'data' => StudentResource::collection($students),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Create student
            $studentData = $request->except('subscription');
            $student = Student::create($studentData);

            // Create subscription if provided
            if ($request->has('subscription') && $request->subscription) {
                $subscriptionData = $request->subscription;

                // Get subscription option to calculate expiry date and price
                $subscriptionOption = SubscriptionOption::findOrFail($subscriptionData['subscription_option_id']);
                $basePrice = $subscriptionData['custom_price'] ?? $subscriptionOption->price;

                // Initialize discount variables
                $discountType = $subscriptionData['discount_type'] ?? null;
                $discountValue = $subscriptionData['discount_value'] ?? null;
                $couponCode = null;
                $coupon = null;

                // Handle coupon if provided
                if (!empty($subscriptionData['coupon_code'])) {
                    try {
                        $couponService = new CouponService();
                        $coupon = $couponService->validateCoupon($subscriptionData['coupon_code']);
                        $calculation = $couponService->calculateDiscount($coupon, $basePrice);

                        // Override discount values with coupon
                        $discountType = $calculation['discount_type'];
                        $discountValue = $calculation['discount_value'];
                        $couponCode = $coupon->code;
                    } catch (ValidationException $e) {
                        throw $e;
                    }
                }

                $subscription = new StudentSubscription([
                    'student_id' => $student->id,
                    'program_id' => $subscriptionData['program_id'],
                    'subscription_option_id' => $subscriptionData['subscription_option_id'],
                    'custom_price' => $subscriptionData['custom_price'] ?? null,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'coupon_code' => $couponCode,
                    'start_date' => $subscriptionData['start_date'],
                    'expiry_date' => now()->parse($subscriptionData['start_date'])->addMonths($subscriptionOption->duration_months),
                    'status' => 'active',
                ]);

                $subscription->save();

                // Apply coupon and create log if coupon was used
                if (!empty($subscriptionData['coupon_code']) && $coupon) {
                    $couponService->applyCoupon(
                        $coupon,
                        $student->id,
                        $basePrice,
                        CouponLog::PURPOSE_INITIAL_SUBSCRIPTION,
                        StudentSubscription::class,
                        $subscription->id,
                        "Applied to initial subscription during student registration"
                    );
                }

                // Update student's last subscription expiry
                $student->update([
                    'last_subscription_expiry' => $subscription->expiry_date,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully.',
                'data' => new StudentResource($student->load(['program', 'subscriptions.subscriptionOption'])),
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create student.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): JsonResponse
    {
        $student->load([
            'program',
            'subscriptions.subscriptionOption',
            'subscriptions.program',
            'payments.items',
            'attachments'
        ]);

        // Calculate financial statistics
        // Calculate total payable: sum of all subscription final prices
        $totalSubscriptionCost = $student->subscriptions->sum('final_price');

        // Calculate total product cost: sum of all product payment items
        $totalProductCost = DB::table('payment_items')
            ->join('payments', 'payment_items.payment_id', '=', 'payments.id')
            ->where('payments.student_id', $student->id)
            ->where('payment_items.item_type', 'product')
            ->sum(DB::raw('payment_items.unit_price * payment_items.quantity'));

        // Total payable = subscriptions + products
        $totalPayable = $totalSubscriptionCost + $totalProductCost;

        // Calculate total paid amount
        $totalPaidAmount = $student->payments()->where('status', 'paid')->sum('amount');

        // Calculate total due
        $totalDueAmount = $totalPayable - $totalPaidAmount;

        // Add financial stats to student instance
        $student->total_subscription_cost = $totalPayable; // This is actually total payable now
        $student->total_paid_amount = $totalPaidAmount;
        $student->total_due_amount = max(0, $totalDueAmount); // Ensure non-negative

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ]);
    }

    /**
     * Update the specified student in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        try {
            $student->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully.',
                'data' => new StudentResource($student->load(['program', 'subscriptions.subscriptionOption'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Student $student): JsonResponse
    {
        try {
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive the specified student.
     */
    public function archive(Student $student): JsonResponse
    {
        try {
            $student->update(['status' => Student::STATUS_ARCHIVED]);

            return response()->json([
                'success' => true,
                'message' => 'Student archived successfully.',
                'data' => new StudentResource($student),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive student.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore an archived student.
     */
    public function restore(Student $student): JsonResponse
    {
        try {
            $student->update(['status' => Student::STATUS_ACTIVE]);

            return response()->json([
                'success' => true,
                'message' => 'Student restored successfully.',
                'data' => new StudentResource($student),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore student.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get archived students.
     */
    public function archived(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $query = Student::archived()
            ->with(['program', 'subscriptions.subscriptionOption'])
            ->orderBy('updated_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StudentResource::collection($students),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }
}
