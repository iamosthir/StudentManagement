<template>
    <div class="expenses" dir="rtl">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-cash-stack me-2"></i>
                    المصروفات
                </h1>
                <p class="page-subtitle">تتبع وإدارة جميع المصروفات</p>
            </div>
            <div class="header-actions">
                <Button
                    label="إدارة الفئات"
                    icon="bi bi-tags"
                    @click="router.push('/expense-categories')"
                    severity="secondary"
                    outlined
                />
                <Button
                    label="إضافة مصروف"
                    icon="bi bi-plus-circle"
                    @click="router.push('/expenses/create')"
                />
            </div>
        </div>

        <!-- Summary Cards -->
        <div v-if="!loadingSummary && summary" class="summary-cards">
            <div class="summary-card">
                <div class="card-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="card-content">
                    <h3>إجمالي المصروفات</h3>
                    <p class="amount">${{ formatMoney(summary.total_amount) }}</p>
                    <span class="count">{{ summary.total_count }} معاملة</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="card-content">
                    <h3>متوسط المصروف</h3>
                    <p class="amount">${{ formatMoney(summary.average_expense) }}</p>
                    <span class="count">لكل معاملة</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label>محفظة المصروف</label>
                <Select
                    v-model="filters.wallet_id"
                    :options="wallets"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="جميع المحافظ"
                    showClear
                    @change="fetchExpenses"
                />
            </div>
            <div class="filter-group">
                <label>الفئة</label>
                <Select
                    v-model="filters.category_id"
                    :options="categories"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="جميع الفئات"
                    showClear
                    @change="fetchExpenses"
                />
            </div>
            <div class="filter-group">
                <label>من تاريخ</label>
                <DatePicker
                    v-model="filters.start_date"
                    dateFormat="yy-mm-dd"
                    placeholder="اختر تاريخ البداية"
                    showIcon
                    @date-select="fetchExpenses"
                />
            </div>
            <div class="filter-group">
                <label>إلى تاريخ</label>
                <DatePicker
                    v-model="filters.end_date"
                    dateFormat="yy-mm-dd"
                    placeholder="اختر تاريخ النهاية"
                    showIcon
                    @date-select="fetchExpenses"
                />
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="data-card">
            <TableSkeleton v-if="loading" :rows="10" :columns="6" />

            <table v-else class="data-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المحفظة</th>
                        <th>الفئة</th>
                        <th>المبلغ</th>
                        <th>الوصف</th>
                        <th>أنشأ بواسطة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="expense in expenses" :key="expense.id">
                        <td>
                            <span class="date">{{ formatDate(expense.date) }}</span>
                        </td>
                        <td>
                            <div class="wallet-info">
                                <i class="bi bi-wallet2"></i>
                                {{ expense.wallet.name }}
                            </div>
                        </td>
                        <td>
                            <span class="category-badge">
                                {{ expense.category.name }}
                            </span>
                        </td>
                        <td>
                            <span class="amount">${{ formatMoney(expense.amount) }}</span>
                        </td>
                        <td>
                            <div class="description">{{ expense.description || '-' }}</div>
                        </td>
                        <td>
                            <div class="admin-info">
                                <div class="name">{{ expense.created_by.name }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <Button
                                    icon="bi bi-pencil"
                                    severity="info"
                                    text
                                    rounded
                                    @click="router.push(`/expenses/${expense.id}/edit`)"
                                    v-tooltip.top="'تعديل'"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!loading && expenses.length === 0" class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>لم يتم العثور على مصروفات</p>
            </div>

            <!-- Pagination -->
            <div v-if="!loading && expenses.length > 0" class="pagination">
                <Button
                    label="السابق"
                    icon="bi bi-chevron-left"
                    @click="previousPage"
                    :disabled="meta.current_page === 1"
                    outlined
                />
                <span class="page-info">
                    الصفحة {{ meta.current_page }} من {{ meta.last_page }} ({{ meta.total }} إجمالي)
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

const loading = ref(false);
const loadingSummary = ref(false);
const expenses = ref([]);
const summary = ref(null);
const wallets = ref([]);
const categories = ref([]);

const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
});

const filters = ref({
    wallet_id: null,
    category_id: null,
    start_date: null,
    end_date: null,
});

onMounted(() => {
    fetchWallets();
    fetchCategories();
    fetchSummary();
    fetchExpenses();
});

const fetchWallets = async () => {
    try {
        const response = await axios.get('/admin/expenses/wallets');
        if (response.data.success) {
            wallets.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching wallets:', error);
    }
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/admin/expense-categories');
        if (response.data.success) {
            categories.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching categories:', error);
    }
};

const fetchSummary = async () => {
    loadingSummary.value = true;
    try {
        const params = {};
        if (filters.value.wallet_id) params.wallet_id = filters.value.wallet_id;
        if (filters.value.start_date) params.start_date = formatDateForApi(filters.value.start_date);
        if (filters.value.end_date) params.end_date = formatDateForApi(filters.value.end_date);

        const response = await axios.get('/admin/expenses/summary', { params });
        if (response.data.success) {
            summary.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching summary:', error);
    } finally {
        loadingSummary.value = false;
    }
};

const fetchExpenses = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, per_page: 20 };
        if (filters.value.wallet_id) params.wallet_id = filters.value.wallet_id;
        if (filters.value.category_id) params.category_id = filters.value.category_id;
        if (filters.value.start_date) params.start_date = formatDateForApi(filters.value.start_date);
        if (filters.value.end_date) params.end_date = formatDateForApi(filters.value.end_date);

        const response = await axios.get('/admin/expenses', { params });
        if (response.data.success) {
            expenses.value = response.data.data;
            meta.value = response.data.meta;
        }

        await fetchSummary();
    } catch (error) {
        console.error('Error fetching expenses:', error);
    } finally {
        loading.value = false;
    }
};

const previousPage = () => {
    if (meta.value.current_page > 1) {
        fetchExpenses(meta.value.current_page - 1);
    }
};

const nextPage = () => {
    if (meta.value.current_page < meta.value.last_page) {
        fetchExpenses(meta.value.current_page + 1);
    }
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatDateForApi = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
</script>

<style scoped>
.expenses {
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
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
    transition: transform 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.card-content h3 {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 0.5rem 0;
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
    text-align: right;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.875rem;
}

.data-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.date {
    font-weight: 600;
    color: #1e293b;
}

.wallet-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #1e293b;
}

.wallet-info i {
    color: #6366f1;
}

.category-badge {
    background: #e0e7ff;
    color: #3730a3;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.amount {
    font-weight: 700;
    font-size: 1.1rem;
    color: #ef4444;
}

.description {
    color: #64748b;
    font-size: 0.875rem;
    max-width: 300px;
}

.admin-info .name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.875rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
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

@media (max-width: 768px) {
    .expenses {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
        flex-direction: column;
    }

    .filters-section {
        grid-template-columns: 1fr;
    }
}
</style>
