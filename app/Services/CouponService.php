<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * Validate and retrieve a coupon by code.
     *
     * @param string $code
     * @return Coupon
     * @throws ValidationException
     */
    public function validateCoupon(string $code): Coupon
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => ['Invalid coupon code.']
            ]);
        }

        if ($coupon->is_used) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon has already been used.']
            ]);
        }

        return $coupon;
    }

    /**
     * Calculate discount amount based on coupon and original amount.
     *
     * @param Coupon $coupon
     * @param float $originalAmount
     * @return array
     */
    public function calculateDiscount(Coupon $coupon, float $originalAmount): array
    {
        $discountAmount = 0;

        if ($coupon->discount_type === Coupon::DISCOUNT_PERCENT) {
            $discountAmount = ($originalAmount * $coupon->discount_value) / 100;
        } else {
            $discountAmount = min($coupon->discount_value, $originalAmount);
        }

        $finalAmount = max(0, $originalAmount - $discountAmount);

        return [
            'original_amount' => round($originalAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'final_amount' => round($finalAmount, 2),
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
        ];
    }

    /**
     * Apply coupon and create log entry.
     *
     * @param Coupon $coupon
     * @param int $studentId
     * @param float $originalAmount
     * @param string $purpose
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $notes
     * @return array
     */
    public function applyCoupon(
        Coupon $coupon,
        int $studentId,
        float $originalAmount,
        string $purpose,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): array {
        // Calculate discount
        $calculation = $this->calculateDiscount($coupon, $originalAmount);

        // Get admin ID if authenticated
        $adminId = Auth::guard('admin')->id();

        // Mark coupon as used
        $coupon->markAsUsed($studentId, $adminId);

        // Create coupon log
        CouponLog::create([
            'student_id' => $studentId,
            'coupon_id' => $coupon->id,
            'admin_id' => $adminId,
            'discount_amount' => $calculation['discount_amount'],
            'original_amount' => $calculation['original_amount'],
            'final_amount' => $calculation['final_amount'],
            'purpose' => $purpose,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);

        return $calculation;
    }

    /**
     * Verify coupon and return calculation without applying it.
     *
     * @param string $code
     * @param float $amount
     * @return array
     * @throws ValidationException
     */
    public function verifyCoupon(string $code, float $amount): array
    {
        $coupon = $this->validateCoupon($code);
        $calculation = $this->calculateDiscount($coupon, $amount);

        return array_merge($calculation, [
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ]);
    }

    /**
     * Get coupon usage history for a student.
     *
     * @param int $studentId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStudentCouponHistory(int $studentId)
    {
        return CouponLog::forStudent($studentId)
            ->with(['coupon', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get total savings for a student from coupons.
     *
     * @param int $studentId
     * @return float
     */
    public function getStudentTotalSavings(int $studentId): float
    {
        return CouponLog::forStudent($studentId)
            ->sum('discount_amount');
    }
}
