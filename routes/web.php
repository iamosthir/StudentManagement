<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentSubscriptionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WalletTransferController;
use App\Http\Controllers\Admin\TransactionLogController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\SubscriptionOptionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CouponController;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
| These routes handle AJAX requests from the Vue frontend
*/

Route::prefix('admin')->group(function () {
    // Public admin routes (login)
    Route::post('login', [AuthController::class, 'login'])->name('admin.login');

    // CSRF cookie endpoint (similar to Sanctum's csrf-cookie)
    Route::get('csrf-cookie', function () {
        // This route exists just to set the XSRF-TOKEN cookie
        // The CSRF token will be automatically added to the response by Laravel
        return response()->noContent();
    })->name('admin.csrf-cookie');

    // Protected admin routes (require auth:admin middleware)
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');
        Route::get('me', [AuthController::class, 'me'])->name('admin.me');

        // Dashboard API
        Route::get('dashboard-stats', [DashboardController::class, 'index'])->name('admin.dashboard.stats');

        // Admin User Management (Administrator only)
        Route::middleware('role:Administrator')->group(function () {
            Route::get('roles', [AdminUserController::class, 'getRoles'])->name('admin.roles');
            Route::resource('admin-users', AdminUserController::class)->except(['create', 'edit']);
        });

        // Student Management
        Route::prefix('students')->group(function () {
            Route::get('/', [StudentController::class, 'index'])->name('admin.students.index');
            Route::post('/', [StudentController::class, 'store'])->name('admin.students.store');
            Route::get('/archived', [StudentController::class, 'archived'])->name('admin.students.archived');
            Route::get('/{student}', [StudentController::class, 'show'])->name('admin.students.show');
            Route::put('/{student}', [StudentController::class, 'update'])->name('admin.students.update');
            Route::delete('/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy');
            Route::post('/{student}/archive', [StudentController::class, 'archive'])->name('admin.students.archive');
            Route::post('/{student}/restore', [StudentController::class, 'restore'])->name('admin.students.restore');
        });

        // Student Subscription Management
        Route::prefix('subscriptions')->group(function () {
            Route::get('/', [StudentSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
            Route::post('/', [StudentSubscriptionController::class, 'store'])->name('admin.subscriptions.store');
            Route::get('/expiring-soon', [StudentSubscriptionController::class, 'expiringSoon'])->name('admin.subscriptions.expiring-soon');
            Route::get('/{subscription}', [StudentSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
            Route::put('/{subscription}', [StudentSubscriptionController::class, 'update'])->name('admin.subscriptions.update');
            Route::delete('/{subscription}', [StudentSubscriptionController::class, 'destroy'])->name('admin.subscriptions.destroy');
            Route::post('/{subscription}/renew', [StudentSubscriptionController::class, 'renew'])->name('admin.subscriptions.renew');
            Route::post('/{subscription}/expire', [StudentSubscriptionController::class, 'expire'])->name('admin.subscriptions.expire');
        });

        // Payment Management
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('admin.payments.index');
            Route::post('/', [PaymentController::class, 'store'])->name('admin.payments.store');
            Route::get('/summary', [PaymentController::class, 'summary'])->name('admin.payments.summary');
            Route::post('/item-payment-info', [PaymentController::class, 'getItemPaymentInfo'])->name('admin.payments.item-payment-info');
            Route::get('/student/{student}/stats', [PaymentController::class, 'studentStats'])->name('admin.payments.student-stats');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('admin.payments.show');
            Route::put('/{payment}', [PaymentController::class, 'update'])->name('admin.payments.update');
            Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('admin.payments.destroy');
            Route::post('/{payment}/mark-paid', [PaymentController::class, 'markAsPaid'])->name('admin.payments.mark-paid');
            Route::post('/{payment}/mark-cancelled', [PaymentController::class, 'markAsCancelled'])->name('admin.payments.mark-cancelled');
        });

        // Wallet Management
        Route::prefix('wallets')->group(function () {
            Route::get('/', [WalletController::class, 'index'])->name('admin.wallets.index');
            Route::get('/my-wallet', [WalletController::class, 'myWallet'])->name('admin.wallets.my-wallet');
            Route::get('/user-wallets/{adminId}', [WalletController::class, 'getUserWallets'])->name('admin.wallets.user-wallets');
            Route::get('/balance-summary', [WalletController::class, 'balanceSummary'])->name('admin.wallets.balance-summary');
            Route::post('/transfer', [WalletController::class, 'transfer'])->name('admin.wallets.transfer');
            Route::post('/expense-wallets', [WalletController::class, 'createExpenseWallet'])->name('admin.wallets.create-expense');

            // Administrator-only routes for creating wallets for users
            Route::middleware('role:Administrator')->group(function () {
                Route::post('/create-for-user', [WalletController::class, 'createWalletForUser'])->name('admin.wallets.create-for-user');
                Route::get('/admins-list', [WalletController::class, 'getAdminsForWalletAssignment'])->name('admin.wallets.admins-list');
            });

            Route::get('/{id}', [WalletController::class, 'show'])->name('admin.wallets.show');
            Route::put('/{id}', [WalletController::class, 'update'])->name('admin.wallets.update');
            Route::get('/{id}/transactions', [WalletController::class, 'transactions'])->name('admin.wallets.transactions');
        });

        // Wallet Transfer Management (All Admins)
        Route::prefix('wallet-transfers')->middleware('json.only')->group(function () {
            Route::get('/', [WalletTransferController::class, 'index'])->name('admin.wallet-transfers.index');
            Route::get('/admins', [WalletTransferController::class, 'getAdmins'])->name('admin.wallet-transfers.admins');
            Route::post('/', [WalletTransferController::class, 'store'])->name('admin.wallet-transfers.store');
            Route::post('/direct-transfer', [WalletTransferController::class, 'directTransfer'])->name('admin.wallet-transfers.direct-transfer');
            Route::get('/{transfer}', [WalletTransferController::class, 'show'])->name('admin.wallet-transfers.show');

            // Administrator Only Routes
            Route::middleware('role:Administrator')->group(function () {
                Route::get('/pending', [WalletTransferController::class, 'pending'])->name('admin.wallet-transfers.pending');
                Route::post('/{transfer}/process', [WalletTransferController::class, 'process'])->name('admin.wallet-transfers.process');
                Route::get('/transaction-logs', [WalletTransferController::class, 'transactionLogs'])->name('admin.wallet-transfers.transaction-logs');
            });
        });

        // Program Management
        Route::prefix('programs')->group(function () {
            Route::get('/', [ProgramController::class, 'index'])->name('admin.programs.index');
            Route::post('/', [ProgramController::class, 'store'])->name('admin.programs.store');
            Route::get('/subscription-options', [ProgramController::class, 'getSubscriptionOptions'])->name('admin.programs.subscription-options');
            Route::get('/products', [ProgramController::class, 'getProducts'])->name('admin.programs.products');
            Route::get('/{program}', [ProgramController::class, 'show'])->name('admin.programs.show');
            Route::put('/{program}', [ProgramController::class, 'update'])->name('admin.programs.update');
            Route::delete('/{program}', [ProgramController::class, 'destroy'])->name('admin.programs.destroy');
        });

        // Subscription Option Management
        Route::prefix('subscription-options')->group(function () {
            Route::get('/', [SubscriptionOptionController::class, 'index'])->name('admin.subscription-options.index');
            Route::post('/', [SubscriptionOptionController::class, 'store'])->name('admin.subscription-options.store');
            Route::get('/{subscriptionOption}', [SubscriptionOptionController::class, 'show'])->name('admin.subscription-options.show');
            Route::put('/{subscriptionOption}', [SubscriptionOptionController::class, 'update'])->name('admin.subscription-options.update');
            Route::delete('/{subscriptionOption}', [SubscriptionOptionController::class, 'destroy'])->name('admin.subscription-options.destroy');
        });

        // Product Management
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('admin.products.index');
            Route::post('/', [ProductController::class, 'store'])->name('admin.products.store');
            Route::get('/types', [ProductController::class, 'getTypes'])->name('admin.products.types');
            Route::get('/{product}', [ProductController::class, 'show'])->name('admin.products.show');
            Route::put('/{product}', [ProductController::class, 'update'])->name('admin.products.update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
        });

        // Coupon Management (Administrator only)
        Route::prefix('coupons')->middleware('role:Administrator')->group(function () {
            Route::get('/', [CouponController::class, 'index'])->name('admin.coupons.index');
            Route::post('/', [CouponController::class, 'store'])->name('admin.coupons.store');
            Route::get('/generate-code', [CouponController::class, 'generateCode'])->name('admin.coupons.generate-code');
            Route::get('/statistics', [CouponController::class, 'statistics'])->name('admin.coupons.statistics');
            Route::post('/verify', [CouponController::class, 'verify'])->name('admin.coupons.verify');
            Route::get('/students-with-coupons', [CouponController::class, 'studentsWithCoupons'])->name('admin.coupons.students');
            Route::get('/{coupon}', [CouponController::class, 'show'])->name('admin.coupons.show');
            Route::put('/{coupon}', [CouponController::class, 'update'])->name('admin.coupons.update');
            Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('admin.coupons.destroy');
        });

        // Expense Category Management
        Route::prefix('expense-categories')->group(function () {
            Route::get('/', [ExpenseCategoryController::class, 'index'])->name('admin.expense-categories.index');
            Route::post('/', [ExpenseCategoryController::class, 'store'])->name('admin.expense-categories.store');
            Route::get('/{category}', [ExpenseCategoryController::class, 'show'])->name('admin.expense-categories.show');
            Route::put('/{category}', [ExpenseCategoryController::class, 'update'])->name('admin.expense-categories.update');
            Route::delete('/{category}', [ExpenseCategoryController::class, 'destroy'])->name('admin.expense-categories.destroy');
        });

        // Expense Management
        Route::prefix('expenses')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('admin.expenses.index');
            Route::post('/', [ExpenseController::class, 'store'])->name('admin.expenses.store');
            Route::get('/summary', [ExpenseController::class, 'summary'])->name('admin.expenses.summary');
            Route::get('/wallets', [ExpenseController::class, 'getExpenseWallets'])->name('admin.expenses.wallets');
            Route::get('/{expense}', [ExpenseController::class, 'show'])->name('admin.expenses.show');
            Route::put('/{expense}', [ExpenseController::class, 'update'])->name('admin.expenses.update');
            Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');
        });

        // Transaction Logs (Administrator only)
        Route::prefix('transaction-logs')->middleware('role:Administrator')->group(function () {
            Route::get('/', [TransactionLogController::class, 'index'])->name('admin.transaction-logs.index');
            Route::get('/summary', [TransactionLogController::class, 'summary'])->name('admin.transaction-logs.summary');
            Route::get('/{log}', [TransactionLogController::class, 'show'])->name('admin.transaction-logs.show');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
| Serve the Vue SPA for all non-AJAX routes
| This MUST be last so API routes are matched first
*/

Route::get('/{any}', function () {
    return view('admin.app');
})->where('any', '.*')->middleware('web');
