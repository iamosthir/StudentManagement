<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\CouponLog;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\TransactionLog;
use App\Services\CouponService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $studentId = $request->get('student_id');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Payment::with(['student', 'admin', 'items'])
            ->orderBy('created_at', 'desc');

        // Filter by student
        if ($studentId) {
            $query->forStudent($studentId);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $payments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Store a newly created payment in storage.
     * Supports partial and full payments.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Get the authenticated admin
            $adminId = Auth::guard('admin')->id();

            // Calculate total amount from items
            $totalAmount = collect($validated['items'])->sum('total_price');

            // Initialize coupon variables
            $couponCode = null;
            $coupon = null;
            $finalAmount = $totalAmount;

            // Handle coupon if provided
            if (!empty($validated['coupon_code'])) {
                try {
                    $couponService = new CouponService();
                    $coupon = $couponService->validateCoupon($validated['coupon_code']);
                    $calculation = $couponService->calculateDiscount($coupon, $totalAmount);

                    $finalAmount = $calculation['final_amount'];
                    $couponCode = $coupon->code;
                } catch (ValidationException $e) {
                    throw $e;
                }
            }

            // Create payment
            $payment = Payment::create([
                'student_id' => $validated['student_id'],
                'admin_id' => $adminId,
                'amount' => $finalAmount,
                'status' => $validated['status'] ?? Payment::STATUS_PAID,
                'payment_method' => $validated['payment_method'] ?? null,
                'coupon_code' => $couponCode,
                'note' => $validated['note'] ?? null,
                'paid_at' => isset($validated['status']) && $validated['status'] === Payment::STATUS_PAID ? now() : null,
            ]);

            // Create payment items
            foreach ($validated['items'] as $itemData) {
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'item_type' => $itemData['item_type'],
                    'item_id' => $itemData['item_id'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_value' => $itemData['discount_value'] ?? 0,
                    'total_price' => $itemData['total_price'],
                ]);

                // If item is a subscription payment, update the subscription renewal
                if ($itemData['item_type'] === PaymentItem::TYPE_SUBSCRIPTION && isset($itemData['renew']) && $itemData['renew']) {
                    $subscription = StudentSubscription::find($itemData['item_id']);
                    if ($subscription) {
                        $subscription->renew();
                    }
                }
            }

            // Update student status if payment is paid
            if ($payment->status === Payment::STATUS_PAID) {
                $student = Student::find($validated['student_id']);
                if ($student && $student->status === Student::STATUS_PENDING_PAYMENT) {
                    $student->update(['status' => Student::STATUS_ACTIVE]);
                }

                // Add payment to staff wallet using WalletService
                $admin = Admin::find($adminId);
                if ($admin) {
                    $walletService = new WalletService();
                    $description = "Payment received from student: {$student->full_name} (#{$student->admission_number})";

                    // Add payment to staff wallet and create wallet transaction
                    $walletService->addPaymentToStaffWallet(
                        $admin,
                        $finalAmount,
                        $payment->id,
                        $description
                    );
                }
            }

            // Apply coupon and create log if coupon was used
            if (!empty($validated['coupon_code']) && $coupon) {
                $couponService = new CouponService();
                $couponService->applyCoupon(
                    $coupon,
                    $validated['student_id'],
                    $totalAmount,
                    CouponLog::PURPOSE_PAYMENT,
                    Payment::class,
                    $payment->id,
                    "Applied to payment #{$payment->id}"
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully.',
                'data' => new PaymentResource($payment->load(['student', 'admin', 'items'])),
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['student', 'admin', 'items']);

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|string|in:paid,pending,cancelled',
                'payment_method' => 'nullable|string|max:255',
                'note' => 'nullable|string',
            ]);

            // If marking as paid, set paid_at timestamp
            if (isset($validated['status']) && $validated['status'] === Payment::STATUS_PAID && !$payment->paid_at) {
                $validated['paid_at'] = now();
            }

            $payment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully.',
                'data' => new PaymentResource($payment->load(['student', 'admin', 'items'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        try {
            // Only allow deletion of pending or cancelled payments
            if ($payment->status === Payment::STATUS_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid payment. Please cancel it first.',
                ], 403);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(Payment $payment): JsonResponse
    {
        DB::beginTransaction();

        try {
            $payment->markAsPaid();

            // Update student status if needed
            $student = $payment->student;
            if ($student->status === Student::STATUS_PENDING_PAYMENT) {
                $student->update(['status' => Student::STATUS_ACTIVE]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as paid.',
                'data' => new PaymentResource($payment->load(['student', 'admin', 'items'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payment as paid.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark payment as cancelled.
     */
    public function markAsCancelled(Payment $payment): JsonResponse
    {
        try {
            $payment->markAsCancelled();

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled.',
                'data' => new PaymentResource($payment->load(['student', 'admin', 'items'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment statistics for a student.
     */
    public function studentStats(Student $student): JsonResponse
    {
        $totalPaid = Payment::forStudent($student->id)
            ->paid()
            ->sum('amount');

        $totalPending = Payment::forStudent($student->id)
            ->pending()
            ->sum('amount');

        $paymentsCount = Payment::forStudent($student->id)
            ->paid()
            ->count();

        $lastPayment = Payment::forStudent($student->id)
            ->paid()
            ->latest('paid_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_paid' => (float) $totalPaid,
                'total_pending' => (float) $totalPending,
                'payments_count' => $paymentsCount,
                'last_payment' => $lastPayment ? new PaymentResource($lastPayment) : null,
            ],
        ]);
    }

    /**
     * Get payments summary/statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Payment::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $totalPaid = (clone $query)->paid()->sum('amount');
        $totalPending = (clone $query)->pending()->sum('amount');
        $totalCancelled = (clone $query)->cancelled()->sum('amount');

        $paidCount = (clone $query)->paid()->count();
        $pendingCount = (clone $query)->pending()->count();
        $cancelledCount = (clone $query)->cancelled()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'paid' => (float) $totalPaid,
                    'pending' => (float) $totalPending,
                    'cancelled' => (float) $totalCancelled,
                ],
                'counts' => [
                    'paid' => $paidCount,
                    'pending' => $pendingCount,
                    'cancelled' => $cancelledCount,
                ],
            ],
        ]);
    }

    /**
     * Get payment info for a subscription or product.
     * Returns total price, paid amount, and remaining amount.
     */
    public function getItemPaymentInfo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'item_type' => 'required|in:subscription,product',
            'item_id' => 'required|integer',
        ]);

        try {
            $studentId = $validated['student_id'];
            $itemType = $validated['item_type'];
            $itemId = $validated['item_id'];

            // Get the item to find the total price and payment rules
            $item = null;
            $totalPrice = 0;
            $isFullPayment = false;

            if ($itemType === 'subscription') {
                $subscription = StudentSubscription::with('subscriptionOption')->find($itemId);
                if (!$subscription || $subscription->student_id != $studentId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Subscription not found or does not belong to this student.',
                    ], 404);
                }
                $item = $subscription;
                $totalPrice = $subscription->final_price;
                $isFullPayment = $subscription->subscriptionOption->is_full_payment ?? false;
            } else {
                $product = \App\Models\Product::find($itemId);
                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found.',
                    ], 404);
                }
                $item = $product;
                $totalPrice = $product->price;
                $isFullPayment = $product->is_full_payment ?? false;
            }

            // Calculate total paid for this specific item
            $paidAmount = PaymentItem::whereHas('payment', function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->where('status', Payment::STATUS_PAID);
            })
                ->where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->sum('total_price');

            $remainingAmount = max(0, $totalPrice - $paidAmount);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_price' => (float) $totalPrice,
                    'paid_amount' => (float) $paidAmount,
                    'remaining_amount' => (float) $remainingAmount,
                    'is_full_payment' => $isFullPayment,
                    'min_payment' => $isFullPayment ? $remainingAmount : 1,
                    'max_payment' => $remainingAmount,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment info.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print invoice - returns a simple HTML view for printing.
     */
    public function printInvoice(Payment $payment)
    {
        $payment->load(['student', 'admin', 'items']);

        // Calculate totals
        $subtotal = $payment->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $totalDiscount = $payment->items->sum('discount_value');

        return view('admin.payments.print-invoice', [
            'payment' => $payment,
            'subtotal' => $subtotal,
            'totalDiscount' => $totalDiscount,
        ]);
    }
}
