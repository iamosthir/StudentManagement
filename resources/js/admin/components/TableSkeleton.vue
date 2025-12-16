<template>
    <div class="table-skeleton">
        <div class="table-responsive">
            <table class="skeleton-table">
                <thead>
                    <tr>
                        <th v-for="col in columns" :key="col">
                            <div class="skeleton-header"></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row">
                        <td v-for="col in columns" :key="`${row}-${col}`">
                            <div class="skeleton-cell" :style="{ animationDelay: `${(row * 0.1) + (col * 0.05)}s` }"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    rows: {
        type: Number,
        default: 5
    },
    columns: {
        type: Number,
        default: 5
    }
});
</script>

<style scoped>
.table-skeleton {
    width: 100%;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.skeleton-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.skeleton-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
}

.skeleton-table thead th {
    padding: 1.25rem 1rem;
    text-align: right;
}

.skeleton-header {
    height: 16px;
    background: linear-gradient(90deg, #e2e8f0 0%, #cbd5e1 50%, #e2e8f0 100%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: shimmer 1.5s ease-in-out infinite;
    width: 80%;
}

.skeleton-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
}

.skeleton-table tbody td {
    padding: 1.25rem 1rem;
}

.skeleton-cell {
    height: 20px;
    background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%);
    background-size: 200% 100%;
    border-radius: 8px;
    animation: shimmer 1.5s ease-in-out infinite;
    width: 90%;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Add magical pulse effect */
.skeleton-cell::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
    animation: slide 2s ease-in-out infinite;
}

@keyframes slide {
    0% {
        left: -100%;
    }
    100% {
        left: 200%;
    }
}

/* Responsive */
@media (max-width: 767px) {
    .skeleton-table thead th,
    .skeleton-table tbody td {
        padding: 1rem 0.75rem;
    }

    .skeleton-header,
    .skeleton-cell {
        width: 100%;
    }
}
</style>
