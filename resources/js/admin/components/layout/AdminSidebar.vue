<template>
    <aside :class="['admin-sidebar', { 'collapsed': collapsed, 'mobile-open': mobileOpen }]">
        <div class="sidebar-content">
            <!-- Header Section with Logo and Toggle -->
            <div class="sidebar-header">
                <!-- Toggle Button (Desktop) -->
                <button
                    class="toggle-btn d-none d-md-flex"
                    @click="$emit('toggle')"
                    title="Toggle Sidebar"
                >
                    <i class="bi bi-list"></i>
                </button>

                <!-- Logo Section -->
                <div class="sidebar-logo">
                    <i class="bi bi-mortarboard-fill"></i>
                    <transition name="fade">
                        <span v-if="!collapsed" class="logo-text">Student MS</span>
                    </transition>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="sidebar-nav">
                <!-- Dashboard -->
                <router-link
                    :to="{ name: 'admin.dashboard' }"
                    class="nav-item"
                    :class="{ 'active': $route.name === 'admin.dashboard' }"
                >
                    <i class="bi bi-speedometer2"></i>
                    <transition name="fade">
                        <span v-if="!collapsed">Dashboard</span>
                    </transition>
                </router-link>

                <!-- Student Management -->
                <div class="nav-group">
                    <div v-if="!collapsed" class="group-label">Student Management</div>
                    <router-link
                        :to="{ name: 'students.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('students') && !$route.query.status }"
                    >
                        <i class="bi bi-people"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Students</span>
                        </transition>
                    </router-link>
                    <router-link
                        :to="{ name: 'students.index', query: { status: 'archived' } }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('students') && $route.query.status === 'archived' }"
                    >
                        <i class="bi bi-archive"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Student Archive</span>
                        </transition>
                    </router-link>
                </div>

                <!-- Programs -->
                <div class="nav-group">
                    <div v-if="!collapsed" class="group-label">Programs</div>
                    <router-link
                        :to="{ name: 'programs.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('programs') }"
                    >
                        <i class="bi bi-book"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Programs</span>
                        </transition>
                    </router-link>
                    <router-link
                        :to="{ name: 'subscription-options.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('subscription-options') }"
                    >
                        <i class="bi bi-bookmark"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Subscription Options</span>
                        </transition>
                    </router-link>
                    <router-link
                        :to="{ name: 'products.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('products') }"
                    >
                        <i class="bi bi-box"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Products</span>
                        </transition>
                    </router-link>
                    <router-link
                        v-if="isAdministrator"
                        :to="{ name: 'coupons.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('coupons') && $route.name !== 'coupons.students' }"
                    >
                        <i class="bi bi-ticket-perforated"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Coupons</span>
                        </transition>
                    </router-link>
                    <router-link
                        v-if="isAdministrator"
                        :to="{ name: 'coupons.students' }"
                        class="nav-item"
                        :class="{ 'active': $route.name === 'coupons.students' }"
                    >
                        <i class="bi bi-people"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Students with Coupons</span>
                        </transition>
                    </router-link>
                </div>

                <!-- Financial -->
                <div class="nav-group">
                    <div v-if="!collapsed" class="group-label">Financial</div>
                    <router-link
                        :to="{ name: 'payments.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('payments') }"
                    >
                        <i class="bi bi-credit-card"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Payments</span>
                        </transition>
                    </router-link>
                    <router-link
                        :to="{ name: 'my-wallet' }"
                        class="nav-item"
                        :class="{ 'active': $route.name === 'my-wallet' }"
                    >
                        <i class="bi bi-wallet-fill"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">My Wallet</span>
                        </transition>
                    </router-link>
                    <router-link
                        v-if="isAdministrator"
                        :to="{ name: 'wallets' }"
                        class="nav-item"
                        :class="{ 'active': $route.name === 'wallets' || $route.name === 'wallet-details' }"
                    >
                        <i class="bi bi-wallet2"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Wallets</span>
                        </transition>
                    </router-link>
                    <router-link
                        :to="{ name: 'wallet-transfers.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('wallet-transfers') }"
                    >
                        <i class="bi bi-arrow-left-right"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Wallet Transfers</span>
                        </transition>
                    </router-link>
                    <router-link
                        :to="{ name: 'expenses.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('expenses') || $route.name?.startsWith('expense-categories') }"
                    >
                        <i class="bi bi-cash-stack"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Expenses</span>
                        </transition>
                    </router-link>
                </div>

                <!-- Logs -->
                <div class="nav-group">
                    <div v-if="!collapsed" class="group-label">Logs</div>
                    <router-link
                        v-if="isAdministrator"
                        :to="{ name: 'transaction-logs.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('transaction-logs') }"
                    >
                        <i class="bi bi-journal-text"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Transaction Logs</span>
                        </transition>
                    </router-link>
                    <router-link
                        v-if="isAdministrator"
                        :to="{ name: 'transfer-logs.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('transfer-logs') }"
                    >
                        <i class="bi bi-arrow-left-right"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Transfer Logs</span>
                        </transition>
                    </router-link>
                </div>

                <!-- System -->
                <div class="nav-group">
                    <div v-if="!collapsed" class="group-label">System</div>
                    <router-link
                        v-if="isAdministrator"
                        :to="{ name: 'admin.users.index' }"
                        class="nav-item"
                        :class="{ 'active': $route.name?.startsWith('admin.users') }"
                    >
                        <i class="bi bi-person-badge"></i>
                        <transition name="fade">
                            <span v-if="!collapsed">Admin Users</span>
                        </transition>
                    </router-link>
                </div>
            </nav>

            <!-- Close Button (Mobile) -->
            <button
                class="close-btn d-md-none"
                @click="$emit('close')"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

defineProps({
    collapsed: {
        type: Boolean,
        default: false
    },
    mobileOpen: {
        type: Boolean,
        default: false
    }
});

defineEmits(['toggle', 'close']);

const adminUser = ref(null);

onMounted(() => {
    const storedUser = localStorage.getItem('admin_user');
    if (storedUser) {
        adminUser.value = JSON.parse(storedUser);
    }
});

const isAdministrator = computed(() => {
    return adminUser.value?.roles?.includes('Administrator') || false;
});
</script>

<style scoped>
.admin-sidebar {
    position: fixed;
    top: 70px;
    right: 0;
    height: calc(100vh - 70px);
    width: 280px;
    background: #ffffff;
    border-left: 1px solid #e2e8f0;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 999;
    overflow: hidden;
}

.admin-sidebar.collapsed {
    width: 80px;
}

.sidebar-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 1.5rem 0;
    position: relative;
}

/* Header Section */
.sidebar-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0 1rem;
    margin-bottom: 2rem;
}

/* Logo */
.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.sidebar-logo i {
    font-size: 2rem;
    min-width: 2rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(5deg); }
}

.logo-text {
    font-size: 1.25rem;
    font-weight: 700;
    white-space: nowrap;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0 0.75rem;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.sidebar-nav::-webkit-scrollbar {
    display: none;
}

/* Nav Groups */
.nav-group {
    margin-bottom: 1.5rem;
}

.group-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #94a3b8;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    letter-spacing: 0.1em;
    position: relative;
}

.group-label::before {
    content: '';
    position: absolute;
    right: 0;
    bottom: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 2px;
}

/* Nav Items */
.nav-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    margin-bottom: 0.25rem;
    border-radius: 12px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    position: relative;
    overflow: hidden;
    font-weight: 500;
}

.nav-item::before {
    content: '';
    position: absolute;
    right: -100%;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    opacity: 0.1;
    transition: right 0.4s ease;
    z-index: -1;
}

.nav-item:hover::before {
    right: 0;
}

.nav-item i {
    font-size: 1.25rem;
    min-width: 1.25rem;
    transition: all 0.3s ease;
}

.nav-item span {
    white-space: nowrap;
    font-weight: 500;
}

.nav-item:hover {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    color: #6366f1;
    transform: translateX(-4px);
}

.nav-item:hover i {
    transform: scale(1.15) rotate(5deg);
}

.nav-item.active {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}

.nav-item.active i {
    animation: bounce 0.6s ease;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.nav-item.active::after {
    content: '';
    position: absolute;
    right: -4px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 40%;
    background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    border-radius: 10px 0 0 10px;
    box-shadow: 0 0 10px rgba(236, 72, 153, 0.5);
}

/* Toggle Button */
.toggle-btn {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    color: #6366f1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.1);
}

.toggle-btn:hover {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.toggle-btn i {
    font-size: 1.25rem;
}

/* Close Button (Mobile) */
.close-btn {
    position: absolute;
    top: 1rem;
    left: 1rem;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.close-btn:hover {
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Mobile Styles */
@media (max-width: 767px) {
    .admin-sidebar {
        width: 100%;
        top: 0;
        height: 100vh;
        transform: translateX(100%);
        z-index: 999;
    }

    .admin-sidebar.mobile-open {
        transform: translateX(0);
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }

    .sidebar-header {
        margin-top: 2rem;
    }
}
</style>
