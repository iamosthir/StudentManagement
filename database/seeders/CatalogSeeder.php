<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SubscriptionOption;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Programs
        $academy = Program::create([
            'name' => 'Academy',
            'description' => 'Full comprehensive educational program with advanced curriculum',
            'is_active' => true,
        ]);

        $edtechPlus = Program::create([
            'name' => 'Edtech Plus',
            'description' => 'Enhanced technology-focused educational program',
            'is_active' => true,
        ]);

        $basicProgram = Program::create([
            'name' => 'Basic Program',
            'description' => 'Standard educational program for beginners',
            'is_active' => true,
        ]);

        // Create Subscription Options
        $monthlyAdvance = SubscriptionOption::create([
            'name' => 'Monthly Advance Payment',
            'price' => 500.00,
            'duration_months' => 1,
            'is_full_payment' => false,
            'is_active' => true,
        ]);

        $quarterlyAdvance = SubscriptionOption::create([
            'name' => 'Quarterly Advance Payment',
            'price' => 1400.00,
            'duration_months' => 3,
            'is_full_payment' => false,
            'is_active' => true,
        ]);

        $annualFull = SubscriptionOption::create([
            'name' => 'Annual Full Payment',
            'price' => 5000.00,
            'duration_months' => 12,
            'is_full_payment' => true,
            'is_active' => true,
        ]);

        $semesterPayment = SubscriptionOption::create([
            'name' => 'Semester Payment',
            'price' => 2700.00,
            'duration_months' => 6,
            'is_full_payment' => false,
            'is_active' => true,
        ]);

        // Create Products
        $mathBook = Product::create([
            'name' => 'Mathematics Textbook',
            'price' => 50.00,
            'type' => Product::TYPE_BOOK,
            'is_active' => true,
        ]);

        $scienceBook = Product::create([
            'name' => 'Science Textbook',
            'price' => 55.00,
            'type' => Product::TYPE_BOOK,
            'is_active' => true,
        ]);

        $summerCamp = Product::create([
            'name' => 'Summer Technology Camp',
            'price' => 300.00,
            'type' => Product::TYPE_CAMP,
            'is_active' => true,
        ]);

        $applicationFee = Product::create([
            'name' => 'Registration Application Fee',
            'price' => 100.00,
            'type' => Product::TYPE_APPLICATION_FEE,
            'is_active' => true,
        ]);

        $uniform = Product::create([
            'name' => 'School Uniform',
            'price' => 75.00,
            'type' => Product::TYPE_UNIFORM,
            'is_active' => true,
        ]);

        $labMaterials = Product::create([
            'name' => 'Laboratory Materials Kit',
            'price' => 120.00,
            'type' => Product::TYPE_MATERIALS,
            'is_active' => true,
        ]);

        // Attach Subscription Options to Programs
        $academy->subscriptionOptions()->attach([
            $monthlyAdvance->id,
            $quarterlyAdvance->id,
            $annualFull->id,
            $semesterPayment->id,
        ]);

        $edtechPlus->subscriptionOptions()->attach([
            $monthlyAdvance->id,
            $quarterlyAdvance->id,
            $annualFull->id,
        ]);

        $basicProgram->subscriptionOptions()->attach([
            $monthlyAdvance->id,
            $semesterPayment->id,
        ]);

        // Attach Products to Programs
        $academy->products()->attach([
            $mathBook->id,
            $scienceBook->id,
            $summerCamp->id,
            $applicationFee->id,
            $uniform->id,
            $labMaterials->id,
        ]);

        $edtechPlus->products()->attach([
            $mathBook->id,
            $scienceBook->id,
            $summerCamp->id,
            $applicationFee->id,
            $labMaterials->id,
        ]);

        $basicProgram->products()->attach([
            $mathBook->id,
            $applicationFee->id,
            $uniform->id,
        ]);

        $this->command->info('Catalog seeded successfully!');
        $this->command->info('Created 3 programs, 4 subscription options, and 6 products.');
    }
}
