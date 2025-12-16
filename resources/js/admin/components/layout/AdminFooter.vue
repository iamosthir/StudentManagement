<template>
    <footer :class="['admin-footer', { 'sidebar-collapsed': sidebarCollapsed }]">
        <div class="footer-content">
            <div class="footer-left">
                <span class="copyright">© {{ currentYear }} Student Management System</span>
                <span class="separator">•</span>
                <span class="rights">All rights reserved</span>
            </div>
            <div class="footer-right">
                <span class="version">Version 1.0.0</span>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';

defineProps({
    sidebarCollapsed: {
        type: Boolean,
        default: false
    }
});

const currentYear = computed(() => new Date().getFullYear());
</script>

<style scoped>
.admin-footer {
    height: 60px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
    margin-top: auto;
}

.footer-content {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
}

.footer-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
}

.copyright {
    color: #2d3748;
    font-weight: 500;
}

.separator {
    color: #cbd5e0;
}

.rights {
    color: #718096;
}

.footer-right {
    font-size: 0.875rem;
}

.version {
    color: #718096;
    font-weight: 500;
    padding: 0.375rem 0.75rem;
    background: rgba(102, 126, 234, 0.08);
    border-radius: 6px;
}

/* Mobile responsive */
@media (max-width: 767px) {
    .admin-footer {
        margin-right: 0;
        height: auto;
        padding: 1rem 0;
    }

    .footer-content {
        flex-direction: column;
        gap: 0.75rem;
        padding: 0 1rem;
    }

    .footer-left {
        flex-direction: column;
        gap: 0.25rem;
        text-align: center;
    }

    .separator {
        display: none;
    }
}
</style>
