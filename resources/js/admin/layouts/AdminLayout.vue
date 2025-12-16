<template>
    <div id="app-wrapper" dir="rtl">
        <!-- Show layout components only for authenticated routes -->
        <template v-if="!isLoginPage">
            <AdminHeader
                @toggle-sidebar="toggleSidebar"
                :sidebar-collapsed="sidebarCollapsed"
            />

            <AdminSidebar
                :collapsed="sidebarCollapsed"
                :mobile-open="mobileMenuOpen"
                @toggle="toggleSidebar"
                @close="closeSidebar"
            />

            <!-- Main Content Area -->
            <main :class="['main-content', { 'sidebar-collapsed': sidebarCollapsed, 'sidebar-mobile-open': mobileMenuOpen }]">
                <router-view />
            </main>

            <AdminFooter :sidebar-collapsed="sidebarCollapsed" />

            <!-- Mobile overlay -->
            <div
                v-if="mobileMenuOpen"
                class="mobile-overlay"
                @click="closeSidebar"
            ></div>
        </template>

        <!-- For login page, show only the router-view without layout -->
        <template v-else>
            <router-view />
        </template>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AdminHeader from '../components/layout/AdminHeader.vue';
import AdminSidebar from '../components/layout/AdminSidebar.vue';
import AdminFooter from '../components/layout/AdminFooter.vue';

const route = useRoute();
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);

// Check if current route is login page
const isLoginPage = computed(() => {
    return route.name === 'admin.login';
});

// Check if mobile
const isMobile = () => window.innerWidth < 768;

// Toggle sidebar
const toggleSidebar = () => {
    if (isMobile()) {
        // On mobile, toggle full open/close
        mobileMenuOpen.value = !mobileMenuOpen.value;
        sidebarCollapsed.value = false; // Always show full sidebar on mobile when open
    } else {
        // On desktop, toggle collapsed/expanded
        sidebarCollapsed.value = !sidebarCollapsed.value;
    }
};

// Close sidebar
const closeSidebar = () => {
    mobileMenuOpen.value = false;
    if (isMobile()) {
        sidebarCollapsed.value = false;
    }
};

// Initialize sidebar state based on screen size
onMounted(() => {
    if (isMobile()) {
        mobileMenuOpen.value = false;
        sidebarCollapsed.value = false;
    } else {
        sidebarCollapsed.value = false; // Start expanded on desktop
    }
});

// Watch for route changes to close mobile menu
watch(() => route.path, () => {
    if (isMobile()) {
        closeSidebar();
    }
});
</script>

<style scoped>
#app-wrapper {
    min-height: 100vh;
    background: #f8fafc;
    position: relative;
}

.main-content {
    margin-top: 70px;
    margin-right: 280px; /* Full sidebar width */
    margin-left: 0;
    padding: 2rem;
    min-height: calc(100vh - 70px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.main-content.sidebar-collapsed {
    margin-right: 80px; /* Collapsed sidebar width (icons only) */
}

/* Mobile overlay */
.mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Mobile responsive */
@media (max-width: 767px) {
    .main-content {
        margin-right: 0;
        margin-left: 0;
        padding: 1rem;
    }

    .main-content.sidebar-mobile-open {
        overflow: hidden;
    }
}
</style>
