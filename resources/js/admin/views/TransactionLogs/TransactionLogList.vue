<template>
    <div class="transaction-logs">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-journal-text me-2"></i>
                    سجلات المعاملات
                </h1>
                <p class="page-subtitle">سجل تدقيق كامل لجميع المعاملات المالية</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div v-if="!loadingSummary && summary" class="summary-cards">
            <div class="summary-card payments">
                <div class="card-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="card-content">
                    <h3>مدفوعات الطلاب</h3>
                    <p class="amount">${{ formatMoney(summary.totals.payments) }}</p>
                    <span class="count">{{ summary.counts.payments }} معاملة</span>
                </div>
            </div>

            <div class="summary-card transfers-in">
                <div class="card-icon">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div class="card-content">
                    <h3>التحويلات الواردة</h3>
                    <p class="amount">${{ formatMoney(summary.totals.transfers_in) }}</p>
                    <span class="count">{{ summary.counts.transfers_in }} معاملة</span>
                </div>
            </div>

            <div class="summary-card transfers-out">
                <div class="card-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div class="card-content">
                    <h3>التحويلات الصادرة</h3>
                    <p class="amount">${{ formatMoney(summary.totals.transfers_out) }}</p>
                    <span class="count">{{ summary.counts.transfers_out }} معاملة</span>
                </div>
            </div>
        </div>

        <TableSkeleton v-if="loadingSummary" :rows="1" :columns="3" />

        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label>نوع المعاملة</label>
                <Select
                    v-model="filters.type"
                    :options="transactionTypes"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="جميع الأنواع"
                    showClear
                    @change="fetchLogs"
                />
            </div>
            <div class="filter-group">
                <label>رقم الدفعة</label>
                <InputNumber
                    v-model="filters.payment_id"
                    placeholder="أدخل رقم الدفعة"
                    @input="fetchLogs"
                />
            </div>
            <div class="filter-group">
                <label>من تاريخ</label>
                <DatePicker
                    v-model="filters.start_date"
                    dateFormat="yy-mm-dd"
                    placeholder="اختر تاريخ البداية"
                    showIcon
                    @date-select="fetchLogs"
                />
            </div>
            <div class="filter-group">
                <label>إلى تاريخ</label>
                <DatePicker
                    v-model="filters.end_date"
                    dateFormat="yy-mm-dd"
                    placeholder="اختر تاريخ النهاية"
                    showIcon
                    @date-select="fetchLogs"
                />
            </div>
        </div>

        <!-- Logs Table -->
        <div class="data-card">
            <TableSkeleton v-if="loading" :rows="10" :columns="8" />

            <table v-else class="data-table">
                <thead>
                    <tr>
                        <th>المعرف</th>
                        <th>التاريخ والوقت</th>
                        <th>المسؤول</th>
                        <th>النوع</th>
                        <th>المبلغ</th>
                        <th>الرصيد قبل</th>
                        <th>الرصيد بعد</th>
                        <th>الوصف</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id">
                        <td>
                            <span class="log-id">#{{ log.id }}</span>
                        </td>
                        <td>
                            <div class="datetime">
                                <div class="date">{{ formatDate(log.created_at) }}</div>
                                <div class="time">{{ formatTime(log.created_at) }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="admin-info">
                                <div class="name">{{ log.admin.name }}</div>
                                <div class="email">{{ log.admin.email }}</div>
                            </div>
                        </td>
                        <td>
                            <span :class="['type-badge', log.transaction_type]">
                                {{ formatType(log.transaction_type) }}
                            </span>
                        </td>
                        <td>
                            <span :class="['amount', getAmountClass(log.transaction_type)]">
                                {{ getAmountPrefix(log.transaction_type) }}${{ formatMoney(log.amount) }}
                            </span>
                        </td>
                        <td>
                            <span class="balance">${{ formatMoney(log.balance_before) }}</span>
                        </td>
                        <td>
                            <span class="balance">${{ formatMoney(log.balance_after) }}</span>
                        </td>
                        <td>
                            <div class="description">
                                {{ log.description }}
                                <span v-if="log.payment_id" class="payment-link">
                                    (Payment #{{ log.payment_id }})
                                </span>
                            </div>
                        </td>
                        <td>
                            <Button
                                icon="bi bi-eye"
                                severity="info"
                                text
                                rounded
                                @click="viewDetails(log)"
                                v-tooltip.top="'عرض التفاصيل'"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!loading && logs.length === 0" class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>لم يتم العثور على سجلات معاملات</p>
            </div>

            <!-- Pagination -->
            <div v-if="!loading && logs.length > 0" class="pagination">
                <Button
                    label="السابق"
                    icon="bi bi-chevron-left"
                    @click="previousPage"
                    :disabled="meta.current_page === 1"
                    outlined
                />
                <span class="page-info">
                    صفحة {{ meta.current_page }} من {{ meta.last_page }} ({{ meta.total }} إجمالي)
                </span>
                <Button
                    label="التالي"
                    icon="bi bi-chevron-right"
                    iconPos="right"
                    @click="nextPage"
                    :disabled="meta.current_page === meta.last_page"
                    outlined
                />
            </div>
        </div>

        <!-- Details Modal -->
        <Dialog
            v-model:visible="showDetailsModal"
            modal
            header="تفاصيل سجل المعاملات"
            :style="{ width: '700px' }"
        >
            <div v-if="selectedLog" class="log-details">
                <div class="detail-row">
                    <span class="label">معرف المعاملة:</span>
                    <span class="value">#{{ selectedLog.id }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">التاريخ والوقت:</span>
                    <span class="value">{{ formatDateTime(selectedLog.created_at) }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">المسؤول:</span>
                    <span class="value">{{ selectedLog.admin.name }} ({{ selectedLog.admin.email }})</span>
                </div>
                <div class="detail-row">
                    <span class="label">نوع المعاملة:</span>
                    <span :class="['value', 'type-badge', selectedLog.transaction_type]">
                        {{ formatType(selectedLog.transaction_type) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">المبلغ:</span>
                    <span :class="['value', 'amount', getAmountClass(selectedLog.transaction_type)]">
                        {{ getAmountPrefix(selectedLog.transaction_type) }}${{ formatMoney(selectedLog.amount) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">الرصيد قبل:</span>
                    <span class="value">${{ formatMoney(selectedLog.balance_before) }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">الرصيد بعد:</span>
                    <span class="value">${{ formatMoney(selectedLog.balance_after) }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">الوصف:</span>
                    <span class="value">{{ selectedLog.description }}</span>
                </div>
                <div v-if="selectedLog.payment" class="detail-row">
                    <span class="label">الدفعة المرتبطة:</span>
                    <span class="value">
                        Payment #{{ selectedLog.payment.id }} -
                        {{ selectedLog.payment.student.full_name }}
                        ({{ selectedLog.payment.student.admission_number }})
                    </span>
                </div>
                <div v-if="selectedLog.metadata" class="detail-row">
                    <span class="label">البيانات الوصفية:</span>
                    <pre class="value metadata">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                </div>
            </div>

            <template #footer>
                <Button
                    label="إغلاق"
                    @click="showDetailsModal = false"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import TableSkeleton from '@/components/TableSkeleton.vue';

const loading = ref(false);
const loadingSummary = ref(false);
const logs = ref([]);
const summary = ref(null);
const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
});

const filters = ref({
    type: null,
    payment_id: null,
    start_date: null,
    end_date: null,
});

const transactionTypes = [
    { label: 'مدفوعات الطلاب', value: 'payment' },
    { label: 'تحويل وارد', value: 'transfer_in' },
    { label: 'تحويل صادر', value: 'transfer_out' },
    { label: 'استرداد', value: 'refund' },
    { label: 'تعديل', value: 'adjustment' },
];

const showDetailsModal = ref(false);
const selectedLog = ref(null);

onMounted(() => {
    fetchSummary();
    fetchLogs();
});

const fetchSummary = async () => {
    loadingSummary.value = true;
    try {
        const params = {};
        if (filters.value.start_date) params.start_date = formatDateForApi(filters.value.start_date);
        if (filters.value.end_date) params.end_date = formatDateForApi(filters.value.end_date);

        const response = await axios.get('/admin/transaction-logs/summary', { params });
        if (response.data.success) {
            summary.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching summary:', error);
    } finally {
        loadingSummary.value = false;
    }
};

const fetchLogs = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            per_page: 20,
        };

        if (filters.value.type) params.type = filters.value.type;
        if (filters.value.payment_id) params.payment_id = filters.value.payment_id;
        if (filters.value.start_date) params.start_date = formatDateForApi(filters.value.start_date);
        if (filters.value.end_date) params.end_date = formatDateForApi(filters.value.end_date);

        const response = await axios.get('/admin/transaction-logs', { params });
        if (response.data.success) {
            logs.value = response.data.data;
            meta.value = response.data.meta;
        }

        // Also update summary
        await fetchSummary();
    } catch (error) {
        console.error('Error fetching logs:', error);
    } finally {
        loading.value = false;
    }
};

const previousPage = () => {
    if (meta.value.current_page > 1) {
        fetchLogs(meta.value.current_page - 1);
    }
};

const nextPage = () => {
    if (meta.value.current_page < meta.value.last_page) {
        fetchLogs(meta.value.current_page + 1);
    }
};

const viewDetails = (log) => {
    selectedLog.value = log;
    showDetailsModal.value = true;
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const formatDate = (datetime) => {
    return new Date(datetime).toLocaleDateString();
};

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString();
};

const formatDateTime = (datetime) => {
    return new Date(datetime).toLocaleString();
};

const formatDateForApi = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const formatType = (type) => {
    const types = {
        'payment': 'دفعة',
        'transfer_in': 'تحويل وارد',
        'transfer_out': 'تحويل صادر',
        'refund': 'استرداد',
        'adjustment': 'تعديل',
    };
    return types[type] || type;
};

const getAmountClass = (type) => {
    if (type === 'payment' || type === 'transfer_in') return 'positive';
    if (type === 'transfer_out') return 'negative';
    return '';
};

const getAmountPrefix = (type) => {
    if (type === 'payment' || type === 'transfer_in') return '+';
    if (type === 'transfer_out') return '-';
    return '';
};
</script>

<style scoped>
.transaction-logs {
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
}

.page-subtitle {
    color: #64748b;
    margin: 0.5rem 0 0 0;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.payments .card-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.transfers-in .card-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.transfers-out .card-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.card-content h3 {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 0.5rem 0;
    font-weight: 600;
}

.card-content .amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.card-content .count {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Filters */
.filters-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

/* Table */
.data-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: #1e293b;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.875rem;
}

.data-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.log-id {
    font-weight: 600;
    color: #6366f1;
}

.datetime {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.datetime .date {
    font-weight: 600;
    color: #1e293b;
}

.datetime .time {
    font-size: 0.875rem;
    color: #64748b;
}

.admin-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.admin-info .name {
    font-weight: 600;
    color: #1e293b;
}

.admin-info .email {
    font-size: 0.875rem;
    color: #64748b;
}

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.type-badge.payment {
    background: #d1fae5;
    color: #065f46;
}

.type-badge.transfer_in {
    background: #dbeafe;
    color: #1e40af;
}

.type-badge.transfer_out {
    background: #fed7aa;
    color: #92400e;
}

.type-badge.refund {
    background: #fce7f3;
    color: #9f1239;
}

.type-badge.adjustment {
    background: #e0e7ff;
    color: #3730a3;
}

.amount {
    font-weight: 600;
    font-size: 1rem;
}

.amount.positive {
    color: #10b981;
}

.amount.negative {
    color: #ef4444;
}

.balance {
    font-weight: 600;
    color: #1e293b;
}

.description {
    max-width: 300px;
    font-size: 0.875rem;
    color: #475569;
}

.payment-link {
    color: #6366f1;
    font-weight: 600;
    font-size: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.page-info {
    color: #64748b;
    font-size: 0.875rem;
}

/* Details Modal */
.log-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
}

.detail-row .label {
    font-weight: 600;
    color: #475569;
}

.detail-row .value {
    color: #1e293b;
    text-align: right;
}

.detail-row .metadata {
    width: 100%;
    padding: 0.5rem;
    background: #1e293b;
    color: #10b981;
    border-radius: 4px;
    font-size: 0.75rem;
    overflow-x: auto;
}

@media (max-width: 768px) {
    .transaction-logs {
        padding: 1rem;
    }

    .filters-section {
        grid-template-columns: 1fr;
    }

    .summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>
