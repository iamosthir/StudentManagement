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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('owner'); // owner_type, owner_id (already creates index, nullable for system wallets)
            $table->string('name');
            $table->enum('type', ['staff', 'main_cashbox', 'expense']);
            $table->decimal('receivable_amount', 15, 2)->default(0);
            $table->decimal('payable_amount', 15, 2)->default(0);
            $table->timestamps();

            // Index for better query performance
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
