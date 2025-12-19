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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique()->comment('5-digit unique coupon code');
            $table->enum('discount_type', ['percent', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete()->comment('Student who used this coupon');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete()->comment('Admin who applied this coupon');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
