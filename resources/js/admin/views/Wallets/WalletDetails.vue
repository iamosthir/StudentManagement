<template>
    <div class="wallet-details">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <Button
                    label="العودة إلى المحافظ"
                    icon="bi bi-arrow-left"
                    severity="secondary"
                    text
                    @click="router.push({ name: 'wallets' })"
                    class="mb-3"
                />
                <h1 v-if="!loading" class="page-title">
                    <i :class="getWalletIcon(wallet.type)"></i>
                    {{ wallet.name }}
                </h1>
                <p v-if="!loading" class="page-subtitle">
                    {{ formatWalletType(wallet.type) }}
                    <span v-if="wallet.owner"> • مملوك لـ {{ wallet.owner.name }}</span>
                </p>
            </div>
        </div>

        <TableSkeleton v-if="loading" :rows="3" :columns="3" />

        <template v-else>
            <!-- Wallet Balance Cards -->
            <div class="balance-cards">
                <div class="balance-card receivable-card">
                    <div class="card-icon">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>المبلغ المستحق</h3>
                        <p class="amount">${{ formatMoney(wallet.receivable_amount) }}</p>
                        <span class="hint">الأموال الواردة</span>
                    </div>
                </div>

                <div class="balance-card payable-card">
                    <div class="card-icon">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>المبلغ المستحق الدفع</h3>
                        <p class="amount">${{ formatMoney(wallet.payable_amount) }}</p>
                        <span class="hint">الأموال الصادرة</span>
                    </div>
                </div>

                <div class="balance-card balance-main-card">
                    <div class="card-icon">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <div class="card-content">
                        <h3>الرصيد الحالي</h3>
                        <p :class="['amount', wallet.balance >= 0 ? 'positive' : 'negative']">
                            ${{ formatMoney(wallet.balance) }}
                        </p>
                        <span class="hint">المستحق - المستحق الدفع</span>
                    </div>
                </div>
            </div>

            <!-- Transaction Filters -->
            <div class="transactions-section">
                <div class="section-header">
                    <h2>سجل المعاملات</h2>
                    <div class="filters">
                        <Select
                            v-model="filters.type"
                            :options="transactionTypes"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="جميع الأنواع"
                            @change="fetchTransactions"
                        />
                        <Select
                            v-model="filters.direction"
                            :options="directions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="جميع الاتجاهات"
                            @change="fetchTransactions"
                        />
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="data-card">
                    <TableSkeleton v-if="loadingTransactions" :rows="10" :columns="6" />

                    <table v-else-if="transactions.length > 0" class="data-table">
                        <thead>
                            <tr>
                                <th>التاريخ والوقت</th>
                                <th>النوع</th>
                                <th>الاتجاه</th>
                                <th>المبلغ</th>
                                <th>الوصف</th>
                                <th>أنشئ بواسطة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="transaction in transactions" :key="transaction.id">
                                <td>
                                    <div class="datetime">
                                        <span class="date">{{ formatDate(transaction.created_at) }}</span>
                                        <span class="time">{{ formatTime(transaction.created_at) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span :class="['type-badge', transaction.transaction_type]">
                                        {{ formatTransactionType(transaction.transaction_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="['direction-badge', transaction.direction]">
                                        <i :class="transaction.direction === 'in' ? 'bi bi-arrow-down' : 'bi bi-arrow-up'"></i>
                                        {{ transaction.direction === 'in' ? 'وارد' : 'صادر' }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="['amount-value', transaction.direction]">
                                        {{ transaction.direction === 'in' ? '+' : '-' }}${{ formatMoney(transaction.amount) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="description">
                                        {{ transaction.description }}
                                        <div v-if="transaction.related_payment" class="related-info">
                                            <i class="bi bi-link-45deg"></i>
                                            دفعة #{{ transaction.related_payment_id }}
                                        </div>
                                        <div v-if="transaction.related_expense" class="related-info">
                                            <i class="bi bi-link-45deg"></i>
                                            مصروف #{{ transaction.related_expense_id }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span v-if="transaction.created_by">
                                        {{ transaction.created_by.name }}
                                    </span>
                                    <span v-else class="text-muted">النظام</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>لم يتم العثور على معاملات</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.total > 0" class="pagination-controls">
                        <Button
                            label="السابق"
                            icon="bi bi-chevron-left"
                            :disabled="pagination.current_page === 1"
                            @click="changePage(pagination.current_page - 1)"
                        />
                        <span class="page-info">
                            صفحة {{ pagination.current_page }} من {{ pagination.last_page }}
                            ({{ pagination.total }} إجمالي)
                        </span>
                        <Button
                            label="التالي"
                            icon="bi bi-chevron-right"
                            iconPos="right"
                            :disabled="pagination.current_page === pagination.last_page"
                            @click="changePage(pagination.current_page + 1)"
                        />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import Select from 'primevue/select';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const loadingTransactions = ref(false);
const wallet = ref({});
const transactions = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

const filters = ref({
    type: null,
    direction: null,
});

const transactionTypes = [
    { label: 'جميع الأنواع', value: null },
    { label: 'دفعة واردة', value: 'payment_in' },
    { label: 'تحويل وارد', value: 'transfer_in' },
    { label: 'تحويل صادر', value: 'transfer_out' },
    { label: 'مصروف', value: 'expense' },
];

const directions = [
    { label: 'جميع الاتجاهات', value: null },
    { label: 'وارد', value: 'in' },
    { label: 'صادر', value: 'out' },
];

onMounted(() => {
    fetchWallet();
    fetchTransactions();
});

const fetchWallet = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/admin/wallets/${route.params.id}`);
        if (response.data.success) {
            wallet.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching wallet:', error);
        router.push({ name: 'wallets' });
    } finally {
        loading.value = false;
    }
};

const fetchTransactions = async (page = 1) => {
    loadingTransactions.value = true;
    try {
        const params = {
            page: page,
            per_page: pagination.value.per_page,
        };

        if (filters.value.type) params.type = filters.value.type;
        if (filters.value.direction) params.direction = filters.value.direction;

        const response = await axios.get(`/admin/wallets/${route.params.id}/transactions`, { params });

        if (response.data.success) {
            transactions.value = response.data.data;
            pagination.value = response.data.meta;
        }
    } catch (error) {
        console.error('Error fetching transactions:', error);
    } finally {
        loadingTransactions.value = false;
    }
};

const changePage = (page) => {
    fetchTransactions(page);
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const formatWalletType = (type) => {
    const types = {
        'staff': 'محفظة الموظف',
        'main_cashbox': 'الخزينة الرئيسية',
        'expense': 'محفظة المصرونات',
    };
    return types[type] || type;
};

const getWalletIcon = (type) => {
    const icons = {
        'staff': 'bi bi-person-badge',
        'main_cashbox': 'bi bi-bank2',
        'expense': 'bi bi-cash-stack',
    };
    return icons[type] || 'bi bi-wallet2';
};

const formatTransactionType = (type) => {
    const types = {
        'payment_in': 'دفعة واردة',
        'transfer_in': 'تحويل وارد',
        'transfer_out': 'تحويل صادر',
        'expense': 'مصروف',
    };
    return types[type] || type;
};

const formatDate = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.wallet-details {
    padding: 2rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-title i {
    font-size: 1.8rem;
}

.page-subtitle {
    color: #64748b;
    margin: 0.5rem 0 0 0;
}

/* Balance Cards */
.balance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.balance-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.balance-card:hover {
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

.receivable-card .card-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.payable-card .card-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.balance-main-card .card-icon {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
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

.card-content .amount.positive {
    color: #10b981;
}

.card-content .amount.negative {
    color: #ef4444;
}

.card-content .hint {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Transactions Section */
.transactions-section {
    margin-top: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.filters {
    display: flex;
    gap: 1rem;
}

/* Table Styles */
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
}

.data-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
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

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.type-badge.payment_in {
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

.type-badge.expense {
    background: #fee2e2;
    color: #991b1b;
}

.direction-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.direction-badge.in {
    background: #d1fae5;
    color: #065f46;
}

.direction-badge.out {
    background: #fee2e2;
    color: #991b1b;
}

.amount-value {
    font-weight: 700;
    font-size: 1rem;
}

.amount-value.in {
    color: #10b981;
}

.amount-value.out {
    color: #ef4444;
}

.description {
    color: #1e293b;
}

.related-info {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #8b5cf6;
    margin-top: 0.25rem;
}

.text-muted {
    color: #94a3b8;
    font-style: italic;
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
.pagination-controls {
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
</style>
