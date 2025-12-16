<template>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h1 class="page-title">Dashboard</h1>
                <button type="button" class="btn-filter">
                    <i class="bi bi-calendar3 me-2"></i>
                    This Month
                    <i class="bi bi-chevron-down ms-2"></i>
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div v-else class="row stats-row">
            <!-- Total Students -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value">{{ formatNumber(stats.total_students?.value || 0) }}</div>
                        <div class="stat-trend" :class="{ 'trend-up': stats.total_students?.trend === 'up', 'trend-down': stats.total_students?.trend === 'down' }">
                            <i :class="`bi bi-arrow-${stats.total_students?.trend === 'up' ? 'up' : 'down'}`"></i>
                            <span>{{ Math.abs(stats.total_students?.growth || 0) }}% from last month</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <i class="bi bi-bookmark-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Active Subscriptions</div>
                        <div class="stat-value">{{ formatNumber(stats.active_subscriptions?.value || 0) }}</div>
                        <div class="stat-trend" :class="{ 'trend-up': stats.active_subscriptions?.trend === 'up', 'trend-down': stats.active_subscriptions?.trend === 'down' }">
                            <i :class="`bi bi-arrow-${stats.active_subscriptions?.trend === 'up' ? 'up' : 'down'}`"></i>
                            <span>{{ Math.abs(stats.active_subscriptions?.growth || 0) }}% from last month</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-info">
                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Monthly Revenue</div>
                        <div class="stat-value">${{ formatMoney(stats.monthly_revenue?.value || 0) }}</div>
                        <div class="stat-trend" :class="{ 'trend-up': stats.monthly_revenue?.trend === 'up', 'trend-down': stats.monthly_revenue?.trend === 'down' }">
                            <i :class="`bi bi-arrow-${stats.monthly_revenue?.trend === 'up' ? 'up' : 'down'}`"></i>
                            <span>{{ Math.abs(stats.monthly_revenue?.growth || 0) }}% from last month</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Pending Payments</div>
                        <div class="stat-value">{{ formatNumber(stats.pending_payments?.value || 0) }}</div>
                        <div class="stat-trend" :class="{ 'trend-up': stats.pending_payments?.trend === 'up', 'trend-down': stats.pending_payments?.trend === 'down' }">
                            <i :class="`bi bi-arrow-${stats.pending_payments?.trend === 'down' ? 'down' : 'up'}`"></i>
                            <span>{{ Math.abs(stats.pending_payments?.growth || 0) }}% from last month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables Row -->
        <div class="row">
            <!-- Chart Placeholder -->
            <div class="col-lg-8 mb-4">
                <div class="glass-card">
                    <div class="glass-card-header">
                        <h6 class="card-title">Revenue Overview</h6>
                        <div class="header-actions">
                            <button class="btn-action">
                                <i class="bi bi-three-dots"></i>
                            </button>
                        </div>
                    </div>
                    <div class="glass-card-body">
                        <RevenueChart
                            v-if="!loading && chartData.labels.length > 0"
                            :labels="chartData.labels"
                            :data="chartData.data"
                        />
                        <div v-else-if="loading" class="chart-placeholder">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading chart...</span>
                            </div>
                        </div>
                        <div v-else class="chart-placeholder">
                            <i class="bi bi-bar-chart"></i>
                            <p class="placeholder-text">No revenue data available</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4 mb-4">
                <div class="glass-card">
                    <div class="glass-card-header">
                        <h6 class="card-title">Recent Activity</h6>
                    </div>
                    <div class="glass-card-body">
                        <div v-if="!loading && recentActivity.length === 0" class="empty-state-small">
                            <i class="bi bi-inbox"></i>
                            <p>No recent activity</p>
                        </div>
                        <div v-else class="activity-list">
                            <div v-for="(activity, index) in recentActivity" :key="index" class="activity-item">
                                <div :class="['activity-icon', `activity-icon-${activity.icon_class}`]">
                                    <i :class="`bi bi-${activity.icon}`"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-text">{{ activity.text }}</p>
                                    <small class="activity-time">{{ activity.time }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Students Table -->
        <div v-if="!loading" class="row">
            <div class="col-12">
                <div class="glass-card">
                    <div class="glass-card-header">
                        <h6 class="card-title">Recent Students</h6>
                        <div class="header-actions">
                            <router-link :to="{ name: 'students.index' }" class="btn-primary">View All</router-link>
                        </div>
                    </div>
                    <div class="glass-card-body">
                        <div v-if="recentStudents.length === 0" class="empty-state">
                            <i class="bi bi-people"></i>
                            <p>No students yet</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Admission #</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Program</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="student in recentStudents" :key="student.id">
                                        <td><span class="table-id">{{ student.admission_number }}</span></td>
                                        <td class="fw-medium">{{ student.full_name }}</td>
                                        <td>{{ student.email || 'N/A' }}</td>
                                        <td>{{ student.program }}</td>
                                        <td><span :class="['status-badge', getStatusClass(student.status)]">{{ getStatusLabel(student.status) }}</span></td>
                                        <td>{{ student.created_at }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import RevenueChart from '../components/RevenueChart.vue';

const loading = ref(false);
const stats = ref({});
const recentStudents = ref([]);
const recentActivity = ref([]);
const rawChartData = ref({ labels: [], data: [] });

const chartData = computed(() => ({
    labels: rawChartData.value.labels || [],
    data: rawChartData.value.data || []
}));

const fetchDashboardData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/admin/dashboard-stats');
        if (response.data.success) {
            stats.value = response.data.data.statistics;
            recentStudents.value = response.data.data.recent_students;
            recentActivity.value = response.data.data.recent_activity;
            rawChartData.value = response.data.data.chart_data || { labels: [], data: [] };
        }
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    } finally {
        loading.value = false;
    }
};

const formatNumber = (num) => {
    return num.toLocaleString();
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

const getStatusClass = (status) => {
    const classes = {
        active: 'status-active',
        pending_payment: 'status-pending',
        expired: 'status-expired',
        archived: 'status-archived'
    };
    return classes[status] || 'status-secondary';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Active',
        pending_payment: 'Pending Payment',
        expired: 'Expired',
        archived: 'Archived'
    };
    return labels[status] || status;
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<style scoped>
.dashboard {
    padding-bottom: 2rem;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.btn-filter {
    height: 42px;
    padding: 0 1.25rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.btn-filter:hover {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
}

/* Statistics Cards */
.stats-row {
    margin-bottom: 2rem;
}

.stat-card {
    height: 100%;
    padding: 1.75rem;
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    position: relative;
    overflow: hidden;
    animation: scaleIn 0.5s ease-out;
    animation-fill-mode: backwards;
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

.stat-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
    border-color: #6366f1;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.5s ease;
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    opacity: 0.05;
    transition: all 0.5s ease;
}

.stat-card-primary::after {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.stat-card-success::after {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card-info::after {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.stat-card-warning::after {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-card:hover::after {
    transform: scale(1.5) translate(-10%, -10%);
    opacity: 0.1;
}

.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
    position: relative;
    z-index: 1;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(-5deg);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}

.stat-card-primary .stat-icon {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.stat-card-success .stat-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.stat-card-info .stat-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.stat-card-warning .stat-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.stat-icon i {
    font-size: 2rem;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.stat-content {
    position: relative;
    z-index: 1;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
}

.stat-value {
    font-size: 2.25rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.stat-card-primary .stat-value {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card-success .stat-value {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card-info .stat-value {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card-warning .stat-value {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card:hover .stat-value {
    transform: scale(1.05);
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    width: fit-content;
    font-weight: 600;
    transition: all 0.3s ease;
}

.stat-card-primary .stat-trend,
.stat-card-success .stat-trend,
.stat-card-info .stat-trend {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.stat-card-warning .stat-trend {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.stat-trend i {
    font-size: 0.875rem;
}

.stat-trend span {
    color: #64748b;
}

/* Modern Cards */
.glass-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    overflow: hidden;
}

.glass-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.15);
    border-color: #6366f1;
}

.glass-card-header {
    padding: 1.75rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.card-title {
    font-size: 1.125rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.btn-action:hover {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.btn-primary {
    height: 38px;
    padding: 0 1.25rem;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}

.glass-card-body {
    padding: 1.75rem;
}

/* Chart Placeholder */
.chart-placeholder {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    border-radius: 16px;
    border: 2px dashed #e2e8f0;
}

.chart-placeholder i {
    font-size: 3.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    animation: float 3s ease-in-out infinite;
}

.placeholder-text {
    font-size: 1rem;
    color: #64748b;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.placeholder-hint {
    font-size: 0.875rem;
    color: #94a3b8;
}

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-radius: 14px;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border: 1px solid transparent;
}

.activity-item:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    border-color: #e0e7ff;
    transform: translateX(-4px);
}

.activity-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s ease;
    font-size: 1.25rem;
}

.activity-item:hover .activity-icon {
    transform: scale(1.1) rotate(5deg);
}

.activity-icon-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.2) 100%);
    color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.activity-icon-info {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%);
    color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.activity-icon-warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%);
    color: #f59e0b;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.activity-icon-secondary {
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.1) 0%, rgba(148, 163, 184, 0.2) 100%);
    color: #94a3b8;
    box-shadow: 0 4px 12px rgba(148, 163, 184, 0.2);
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 0.9375rem;
    color: #1e293b;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.activity-time {
    font-size: 0.8125rem;
    color: #94a3b8;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
}

.data-table thead th {
    padding: 1.25rem 1rem;
    text-align: right;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #e0e7ff;
}

.data-table thead th:first-child {
    border-top-right-radius: 12px;
}

.data-table thead th:last-child {
    border-top-left-radius: 12px;
}

.data-table tbody tr {
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border-bottom: 1px solid #f1f5f9;
}

.data-table tbody tr:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);
}

.data-table tbody td {
    padding: 1.25rem 1rem;
    text-align: right;
    font-size: 0.9375rem;
    color: #64748b;
    font-weight: 500;
}

.data-table tbody td.fw-medium {
    color: #1e293b;
    font-weight: 600;
}

.table-id {
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
    color: #6366f1;
}

/* Status Badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8125rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.status-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}

.status-active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.15) 100%);
    color: #059669;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.status-active::before {
    background: #10b981;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0);
    }
}

.status-pending {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.15) 100%);
    color: #d97706;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
}

.status-pending::before {
    background: #f59e0b;
}

/* Loading State */
.loading-state {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
}

.empty-state-small {
    text-align: center;
    padding: 2rem 1rem;
    color: #94a3b8;
}

.empty-state-small i {
    font-size: 2rem;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.empty-state-small p {
    margin: 0;
    font-size: 0.875rem;
}

/* Trend Indicators */
.trend-up {
    color: #10b981 !important;
}

.trend-down {
    color: #ef4444 !important;
}

/* Responsive */
@media (max-width: 767px) {
    .page-title {
        font-size: 1.5rem;
    }

    .stat-card {
        padding: 1.5rem;
    }

    .stat-value {
        font-size: 1.875rem;
    }

    .stat-icon {
        width: 56px;
        height: 56px;
    }

    .glass-card-header,
    .glass-card-body {
        padding: 1.25rem;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
    }
}
</style>
