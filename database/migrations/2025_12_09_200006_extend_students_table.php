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
        Schema::table('students', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('birthdate');
            $table->string('academic_year')->nullable()->after('gender');
            $table->foreignId('program_id')->nullable()->after('academic_year')->constrained()->nullOnDelete();
            $table->string('class_section')->nullable()->after('program_id');
            $table->text('address')->nullable()->after('class_section');
            $table->timestamp('last_subscription_expiry')->nullable()->after('status');

            // Add index for archive queries (finding students with expired subscriptions)
            $table->index(['status', 'last_subscription_expiry'], 'idx_student_status_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_student_status_expiry');
            $table->dropForeign(['program_id']);
            $table->dropColumn([
                'gender',
                'academic_year',
                'program_id',
                'class_section',
                'address',
                'last_subscription_expiry',
            ]);
        });
    }
};
