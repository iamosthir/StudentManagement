<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupon_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->comment('Actual discount amount applied');
            $table->decimal('original_amount', 10, 2)->comment('Original amount before discount');
            $table->decimal('final_amount', 10, 2)->comment('Final amount after discount');
            $table->string('purpose')->comment('Where coupon was applied: initial_subscription, subscription, payment, installment');
            $table->string('reference_type')->nullable()->comment('Model type: Payment, StudentSubscription, etc.');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID of the related record');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['student_id', 'created_at']);
            $table->index(['coupon_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_logs');
    }
};
