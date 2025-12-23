<template>
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <!-- Left Side: Toggle Button -->
                <button
                    class="toggle-btn d-md-none"
                    @click="$emit('toggle-sidebar')"
                    aria-label="Toggle menu"
                >
                    <i class="bi bi-list"></i>
                </button>

                <!-- Brand / Logo -->
                <router-link :to="{ name: 'admin.dashboard' }" class="brand">
                    <i class="bi bi-mortarboard-fill"></i>
                    <span class="brand-text d-none d-sm-inline">إدارة الطلاب</span>
                </router-link>

                <!-- Right Side Items -->
                <div class="header-actions">
                    <!-- Notifications -->
                    <button
                        class="action-btn position-relative"
                        type="button"
                        @click="toggleNotifications"
                        ref="notificationBtn"
                    >
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <Menu ref="notificationMenu" :model="notificationItems" :popup="true" class="notification-menu">
                        <template #start>
                            <div class="menu-header">الإشعارات</div>
                        </template>
                    </Menu>

                    <!-- User Menu -->
                    <button
                        class="action-btn user-btn"
                        type="button"
                        @click="toggleUserMenu"
                        ref="userBtn"
                    >
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-md-inline ms-2">{{ adminName }}</span>
                        <i class="bi bi-chevron-down ms-1 d-none d-md-inline"></i>
                    </button>
                    <Menu ref="userMenu" :model="userMenuItems" :popup="true" class="user-menu" />
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Menu from 'primevue/menu';

defineEmits(['toggle-sidebar']);

const router = useRouter();
const adminUser = ref(null);
const notificationMenu = ref(null);
const userMenu = ref(null);
const notificationBtn = ref(null);
const userBtn = ref(null);

// Load admin user from localStorage on mount
onMounted(() => {
    const storedUser = localStorage.getItem('admin_user');
    if (storedUser) {
        adminUser.value = JSON.parse(storedUser);
    }
});

const adminName = computed(() => {
    return adminUser.value ? adminUser.value.name : 'Admin User';
});

// Notification menu items
const notificationItems = ref([
    {
        label: 'تسجيل طالب جديد',
        icon: 'bi bi-person-plus',
        command: () => {
            // Handle notification click
        }
    },
    {
        label: 'تم استلام الدفعة',
        icon: 'bi bi-cash',
        command: () => {
            // Handle notification click
        }
    },
    {
        label: 'الاشتراك على وشك الانتهاء',
        icon: 'bi bi-exclamation-circle',
        command: () => {
            // Handle notification click
        }
    }
]);

// User menu items
const userMenuItems = ref([
    {
        label: 'الملف الشخصي',
        icon: 'bi bi-person',
        command: () => {
            // Navigate to profile
        }
    },
    {
        label: 'الإعدادات',
        icon: 'bi bi-gear',
        command: () => {
            // Navigate to settings
        }
    },
    {
        separator: true
    },
    {
        label: 'تسجيل الخروج',
        icon: 'bi bi-box-arrow-right',
        class: 'text-danger',
        command: () => {
            logout();
        }
    }
]);

// Toggle notifications menu
const toggleNotifications = (event) => {
    notificationMenu.value.toggle(event);
};

// Toggle user menu
const toggleUserMenu = (event) => {
    userMenu.value.toggle(event);
};

const logout = async () => {
    try {
        await axios.post('/admin/logout');
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        // Clear authentication data
        localStorage.removeItem('admin_authenticated');
        localStorage.removeItem('admin_user');

        // Redirect to login
        router.push({ name: 'admin.login' });
    }
};
</script>

<style scoped>
.admin-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    z-index: 1000;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-content {
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

/* Toggle Button */
.toggle-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.1);
}

.toggle-btn:hover {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.toggle-btn i {
    font-size: 1.5rem;
}

/* Brand */
.brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: #1e293b;
    font-weight: 700;
    font-size: 1.25rem;
    transition: all 0.3s ease;
}

.brand:hover {
    transform: translateY(-2px);
}

.brand i {
    font-size: 2rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.brand-text {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Header Actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.action-btn {
    height: 42px;
    padding: 0 1rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    font-weight: 500;
    font-size: 0.9375rem;
}

.action-btn:hover {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.action-btn i {
    font-size: 1.25rem;
    transition: transform 0.3s ease;
}

.action-btn:hover i {
    transform: scale(1.1);
}

.user-btn {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

/* Notification Badge */
.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border-radius: 50%;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
    }
}

/* Menu Header */
.menu-header {
    font-weight: 600;
    color: #1e293b;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    border-radius: 8px;
    margin-bottom: 0.25rem;
}

/* PrimeVue Menu Customization */
:deep(.p-menu) {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    border-radius: 16px;
    padding: 0.5rem;
    min-width: 250px;
}

:deep(.p-menu .p-menuitem-link) {
    padding: 0.75rem 1rem;
    border-radius: 10px;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
}

:deep(.p-menu .p-menuitem-link:hover) {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    color: #6366f1;
}

:deep(.p-menu .p-menuitem-link .p-menuitem-icon) {
    width: 20px;
    font-size: 1.1rem;
    margin-left: 0.5rem;
}

:deep(.p-menu .p-menuitem-link.text-danger) {
    color: #ef4444;
}

:deep(.p-menu .p-menuitem-link.text-danger:hover) {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

:deep(.p-menu .p-menuitem-separator) {
    margin: 0.5rem 0;
    border-color: #f1f5f9;
}

/* Responsive */
@media (max-width: 767px) {
    .header-actions {
        gap: 0.5rem;
    }

    .action-btn {
        width: 42px;
        padding: 0;
        border-radius: 12px;
    }

    .user-btn span {
        display: none;
    }
}
</style>
