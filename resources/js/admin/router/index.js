import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';

// Import views
import Dashboard from '../views/Dashboard.vue';
import AdminLogin from '../views/Auth/AdminLogin.vue';
import AdminUserList from '../views/AdminUsers/AdminUserList.vue';
import AdminUserForm from '../views/AdminUsers/AdminUserForm.vue';

// Student Management
import StudentList from '../views/Students/StudentList.vue';
import StudentForm from '../views/Students/StudentForm.vue';
import StudentDetails from '../views/Students/StudentDetails.vue';

// Payment Management
import PaymentList from '../views/Payments/PaymentList.vue';
import PaymentForm from '../views/Payments/PaymentForm.vue';
import PaymentDetails from '../views/Payments/PaymentDetails.vue';

// Program Management
import ProgramList from '../views/Programs/ProgramList.vue';
import ProgramForm from '../views/Programs/ProgramForm.vue';

// Subscription Option Management
import SubscriptionOptionList from '../views/SubscriptionOptions/SubscriptionOptionList.vue';
import SubscriptionOptionForm from '../views/SubscriptionOptions/SubscriptionOptionForm.vue';

// Product Management
import ProductList from '../views/Products/ProductList.vue';
import ProductForm from '../views/Products/ProductForm.vue';

// Subscription Management
import SubscriptionForm from '../views/Subscriptions/SubscriptionForm.vue';

// Wallet Management
import WalletList from '../views/Wallets/WalletList.vue';
import WalletDetails from '../views/Wallets/WalletDetails.vue';

// Wallet Transfer Management
import TransferHistory from '../views/WalletTransfers/TransferHistory.vue';
import TransferForm from '../views/WalletTransfers/TransferForm.vue';
import PendingTransfers from '../views/WalletTransfers/PendingTransfers.vue';

// Transaction Logs Management
import TransactionLogList from '../views/TransactionLogs/TransactionLogList.vue';

// Expense Management
import ExpenseCategoryList from '../views/ExpenseCategories/ExpenseCategoryList.vue';
import ExpenseList from '../views/Expenses/ExpenseList.vue';
import ExpenseForm from '../views/Expenses/ExpenseForm.vue';

const routes = [
    {
        path: '/',
        redirect: '/admin/login'
    },
    {
        path: '/admin/login',
        name: 'admin.login',
        component: AdminLogin,
        meta: { requiresGuest: true }
    },
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/admin-users',
        name: 'admin.users.index',
        component: AdminUserList,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/admin-users/create',
        name: 'admin.users.create',
        component: AdminUserForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin/admin-users/:id/edit',
        name: 'admin.users.edit',
        component: AdminUserForm,
        meta: { requiresAuth: true }
    },

    // Student Routes
    {
        path: '/students',
        name: 'students.index',
        component: StudentList,
        meta: { requiresAuth: true }
    },
    {
        path: '/students/create',
        name: 'students.create',
        component: StudentForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/students/:id',
        name: 'students.show',
        component: StudentDetails,
        meta: { requiresAuth: true }
    },
    {
        path: '/students/:id/edit',
        name: 'students.edit',
        component: StudentForm,
        meta: { requiresAuth: true }
    },

    // Payment Routes
    {
        path: '/payments',
        name: 'payments.index',
        component: PaymentList,
        meta: { requiresAuth: true }
    },
    {
        path: '/payments/create',
        name: 'payments.create',
        component: PaymentForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/payments/:id',
        name: 'payments.show',
        component: PaymentDetails,
        meta: { requiresAuth: true }
    },

    // Program Routes
    {
        path: '/programs',
        name: 'programs.index',
        component: ProgramList,
        meta: { requiresAuth: true }
    },
    {
        path: '/programs/create',
        name: 'programs.create',
        component: ProgramForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/programs/:id/edit',
        name: 'programs.edit',
        component: ProgramForm,
        meta: { requiresAuth: true }
    },

    // Subscription Option Routes
    {
        path: '/subscription-options',
        name: 'subscription-options.index',
        component: SubscriptionOptionList,
        meta: { requiresAuth: true }
    },
    {
        path: '/subscription-options/create',
        name: 'subscription-options.create',
        component: SubscriptionOptionForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/subscription-options/:id/edit',
        name: 'subscription-options.edit',
        component: SubscriptionOptionForm,
        meta: { requiresAuth: true }
    },

    // Product Routes
    {
        path: '/products',
        name: 'products.index',
        component: ProductList,
        meta: { requiresAuth: true }
    },
    {
        path: '/products/create',
        name: 'products.create',
        component: ProductForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/products/:id/edit',
        name: 'products.edit',
        component: ProductForm,
        meta: { requiresAuth: true }
    },

    // Subscription Routes
    {
        path: '/subscriptions/create',
        name: 'subscriptions.create',
        component: SubscriptionForm,
        meta: { requiresAuth: true }
    },

    // Wallet Routes
    {
        path: '/wallets',
        name: 'wallets',
        component: WalletList,
        meta: { requiresAuth: true }
    },
    {
        path: '/wallets/:id',
        name: 'wallet-details',
        component: WalletDetails,
        meta: { requiresAuth: true }
    },

    // Wallet Transfer Routes
    {
        path: '/transfers',
        name: 'wallet-transfers.index',
        component: TransferHistory,
        meta: { requiresAuth: true }
    },
    {
        path: '/transfers/create',
        name: 'wallet-transfers.create',
        component: TransferForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/transfers/pending',
        name: 'wallet-transfers.pending',
        component: PendingTransfers,
        meta: { requiresAuth: true }
    },
    {
        path: '/transfers/transaction-logs',
        name: 'wallet-transfers.transaction-logs',
        component: TransactionLogList,
        meta: { requiresAuth: true }
    },

    // Transaction Logs Routes (Administrator only)
    {
        path: '/transaction-logs',
        name: 'transaction-logs.index',
        component: TransactionLogList,
        meta: { requiresAuth: true }
    },

    // Expense Category Routes
    {
        path: '/expense-categories',
        name: 'expense-categories.index',
        component: ExpenseCategoryList,
        meta: { requiresAuth: true }
    },

    // Expense Routes
    {
        path: '/expenses',
        name: 'expenses.index',
        component: ExpenseList,
        meta: { requiresAuth: true }
    },
    {
        path: '/expenses/create',
        name: 'expenses.create',
        component: ExpenseForm,
        meta: { requiresAuth: true }
    },
    {
        path: '/expenses/:id/edit',
        name: 'expenses.edit',
        component: ExpenseForm,
        meta: { requiresAuth: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation guard for authentication
router.beforeEach(async (to, from, next) => {
    const isAuthenticated = checkAuthentication();

    if (to.meta.requiresAuth) {
        if (!isAuthenticated) {
            // Not authenticated, redirect to login
            next({ name: 'admin.login' });
        } else {
            // Optimistic authentication - allow navigation
            // The backend will enforce auth on API calls
            next();
        }
    } else if (to.meta.requiresGuest) {
        if (isAuthenticated) {
            // Already authenticated, redirect to dashboard
            next({ name: 'admin.dashboard' });
        } else {
            next();
        }
    } else {
        next();
    }
});

/**
 * Check if user is authenticated based on localStorage
 * This is an optimistic check - actual authentication is enforced on the backend
 */
function checkAuthentication() {
    return localStorage.getItem('admin_authenticated') === 'true';
}

/**
 * Verify authentication with backend
 * Call this when you need to verify the session is still valid
 */
export async function verifyAuth() {
    try {
        const response = await axios.get('/admin/me');
        if (response.data.authenticated) {
            localStorage.setItem('admin_authenticated', 'true');
            localStorage.setItem('admin_user', JSON.stringify(response.data.admin));
            return true;
        }
    } catch {
        // Session expired or not authenticated
        localStorage.removeItem('admin_authenticated');
        localStorage.removeItem('admin_user');
    }
    return false;
}

export default router;
