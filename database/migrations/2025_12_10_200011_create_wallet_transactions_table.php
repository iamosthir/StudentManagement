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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedBigInteger('related_expense_id')->nullable();
            $table->enum('transaction_type', ['payment_in', 'transfer_in', 'transfer_out', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->enum('direction', ['in', 'out']);
            $table->text('description')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('wallet_id');
            $table->index(['wallet_id', 'transaction_type']);
            $table->index(['wallet_id', 'direction']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
