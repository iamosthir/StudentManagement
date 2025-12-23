<template>
    <div class="my-wallet-page">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-wallet-fill"></i>
                    محفظتي
                </h1>
                <p class="page-subtitle">عرض محافظك والأرصدة والمعاملات الأخيرة</p>
            </div>
        </div>

        <TableSkeleton v-if="loading" :rows="5" :columns="3" />

        <template v-else>
            <!-- Summary Cards -->
            <div class="summary-section">
                <div class="summary-card total-balance">
                    <div class="card-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="card-content">
                        <h3>إجمالي الرصيد</h3>
                        <p :class="['amount', summary.total_balance >= 0 ? 'positive' : 'negative']">
                            ${{ formatMoney(summary.total_balance) }}
                        </p>
                        <span class="hint">عبر {{ summary.wallet_count }} {{ summary.wallet_count === 1 ? 'محفظة' : 'محفظة' }}</span>
                    </div>
                </div>

                <div class="summary-card receivable">
                    <div class="card-icon">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>إجمالي المستحق</h3>
                        <p class="amount">${{ formatMoney(summary.total_receivable) }}</p>
                        <span class="hint">الأموال الواردة</span>
                    </div>
                </div>

                <div class="summary-card payable">
                    <div class="card-icon">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>إجمالي المستحق الدفع</h3>
                        <p class="amount">${{ formatMoney(summary.total_payable) }}</p>
                        <span class="hint">الأموال الصادرة</span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="wallets.length === 0" class="empty-state">
                <i class="bi bi-wallet2"></i>
                <h3>لم يتم العثور على محافظ</h3>
                <p>ليس لديك أي محافظ حتى الآن. يتم إنشاء المحافظ تلقائيًا عند استلام المدفوعات.</p>
            </div>

            <!-- Wallets List -->
            <div v-else class="wallets-section">
                <div v-for="wallet in wallets" :key="wallet.id" class="wallet-card">
                    <div class="wallet-header">
                        <div class="wallet-info">
                            <div class="wallet-title">
                                <i :class="getWalletIcon(wallet.type)"></i>
                                <h3>{{ wallet.name }}</h3>
                            </div>
                            <span :class="['wallet-type-badge', wallet.type]">
                                {{ formatWalletType(wallet.type) }}
                            </span>
                        </div>
                        <Button
                            label="عرض التفاصيل"
                            icon="bi bi-arrow-right"
                            iconPos="right"
                            severity="secondary"
                            text
                            @click="router.push({ name: 'wallet-details', params: { id: wallet.id } })"
                        />
                    </div>

                    <!-- Balance Info -->
                    <div class="wallet-balance">
                        <div class="balance-item">
                            <span class="label">
                                <i class="bi bi-arrow-down-circle"></i>
                                مستحق
                            </span>
                            <span class="value receivable">${{ formatMoney(wallet.receivable_amount) }}</span>
                        </div>
                        <div class="balance-item">
                            <span class="label">
                                <i class="bi bi-arrow-up-circle"></i>
                                مستحق الدفع
                            </span>
                            <span class="value payable">${{ formatMoney(wallet.payable_amount) }}</span>
                        </div>
                        <div class="balance-item main">
                            <span class="label">
                                <i class="bi bi-calculator"></i>
                                الرصيد
                            </span>
                            <span :class="['value', 'balance', wallet.balance >= 0 ? 'positive' : 'negative']">
                                ${{ formatMoney(wallet.balance) }}
                            </span>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div v-if="wallet.recent_transactions && wallet.recent_transactions.length > 0" class="recent-transactions">
                        <h4>
                            <i class="bi bi-clock-history"></i>
                            المعاملات الأخيرة
                        </h4>
                        <div class="transactions-list">
                            <div
                                v-for="transaction in wallet.recent_transactions"
                                :key="transaction.id"
                                class="transaction-item"
                            >
                                <div class="transaction-info">
                                    <span :class="['direction-icon', transaction.direction]">
                                        <i :class="transaction.direction === 'in' ? 'bi bi-arrow-down-circle-fill' : 'bi bi-arrow-up-circle-fill'"></i>
                                    </span>
                                    <div class="transaction-details">
                                        <span class="transaction-desc">{{ transaction.description }}</span>
                                        <span class="transaction-date">{{ formatDateTime(transaction.created_at) }}</span>
                                    </div>
                                </div>
                                <span :class="['transaction-amount', transaction.direction]">
                                    {{ transaction.direction === 'in' ? '+' : '-' }}${{ formatMoney(transaction.amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="no-transactions">
                        <i class="bi bi-inbox"></i>
                        <span>لا توجد معاملات بعد</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const loading = ref(true);
const wallets = ref([]);
const summary = ref({
    total_balance: 0,
    total_receivable: 0,
    total_payable: 0,
    wallet_count: 0,
});

onMounted(() => {
    fetchMyWallet();
});

const fetchMyWallet = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/admin/wallets/my-wallet');
        if (response.data.success) {
            wallets.value = response.data.data.wallets;
            summary.value = response.data.data.summary;
        }
    } catch (error) {
        console.error('Error fetching wallet data:', error);
    } finally {
        loading.value = false;
    }
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const formatDateTime = (dateTime) => {
    const date = new Date(dateTime);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const getWalletIcon = (type) => {
    const icons = {
        staff: 'bi bi-person-circle',
        main_cashbox: 'bi bi-bank',
        expense: 'bi bi-cash-stack',
    };
    return icons[type] || 'bi bi-wallet2';
};

const formatWalletType = (type) => {
    const types = {
        staff: 'محفظة الموظف',
        main_cashbox: 'الخزينة الرئيسية',
        expense: 'محفظة المصروفات',
    };
    return types[type] || type;
};
</script>

<style scoped>
.my-wallet-page {
    padding: 2rem;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title i {
    font-size: 2.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.page-subtitle {
    color: #64748b;
    font-size: 1rem;
}

/* Summary Cards */
.summary-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border: 2px solid transparent;
}

.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.summary-card.total-balance {
    border-color: #6366f1;
}

.summary-card.total-balance:hover {
    box-shadow: 0 12px 24px rgba(99, 102, 241, 0.2);
}

.summary-card.receivable {
    border-color: #10b981;
}

.summary-card.receivable:hover {
    box-shadow: 0 12px 24px rgba(16, 185, 129, 0.2);
}

.summary-card.payable {
    border-color: #f59e0b;
}

.summary-card.payable:hover {
    box-shadow: 0 12px 24px rgba(245, 158, 11, 0.2);
}

.summary-card .card-icon {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.total-balance .card-icon {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.receivable .card-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.payable .card-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.summary-card .card-icon i {
    font-size: 2rem;
    color: white;
}

.summary-card .card-content h3 {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.summary-card .amount {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.summary-card .amount.positive {
    color: #10b981;
}

.summary-card .amount.negative {
    color: #ef4444;
}

.summary-card .hint {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Empty State */
.empty-state {
    background: white;
    border-radius: 16px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #475569;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #94a3b8;
}

/* Wallets Section */
.wallets-section {
    display: grid;
    gap: 1.5rem;
}

.wallet-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border: 2px solid transparent;
}

.wallet-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-color: #e0e7ff;
}

.wallet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f1f5f9;
}

.wallet-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.wallet-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.wallet-title i {
    font-size: 1.5rem;
    color: #6366f1;
}

.wallet-title h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.wallet-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.wallet-type-badge.staff {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    color: #6366f1;
}

.wallet-type-badge.expense {
    background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
    color: #f59e0b;
}

.wallet-type-badge.main_cashbox {
    background: linear-gradient(135deg, #d1fae5 0%, #bbf7d0 100%);
    color: #10b981;
}

/* Wallet Balance */
.wallet-balance {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.balance-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 12px;
    background: #f8fafc;
}

.balance-item.main {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
}

.balance-item .label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.balance-item .label i {
    font-size: 1rem;
}

.balance-item .value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}

.balance-item .value.receivable {
    color: #10b981;
}

.balance-item .value.payable {
    color: #f59e0b;
}

.balance-item .value.balance.positive {
    color: #10b981;
}

.balance-item .value.balance.negative {
    color: #ef4444;
}

/* Recent Transactions */
.recent-transactions h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 1rem;
}

.recent-transactions h4 i {
    color: #6366f1;
}

.transactions-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.transaction-item:hover {
    background: #f1f5f9;
}

.transaction-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.direction-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.direction-icon.in {
    background: #d1fae5;
    color: #10b981;
}

.direction-icon.out {
    background: #fed7aa;
    color: #f59e0b;
}

.direction-icon i {
    font-size: 1rem;
}

.transaction-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.transaction-desc {
    font-size: 0.875rem;
    color: #1e293b;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.transaction-date {
    font-size: 0.75rem;
    color: #94a3b8;
}

.transaction-amount {
    font-size: 1rem;
    font-weight: 700;
    flex-shrink: 0;
}

.transaction-amount.in {
    color: #10b981;
}

.transaction-amount.out {
    color: #f59e0b;
}

.no-transactions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 2rem;
    color: #94a3b8;
    font-size: 0.875rem;
}

.no-transactions i {
    font-size: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .my-wallet-page {
        padding: 1rem;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .summary-section {
        grid-template-columns: 1fr;
    }

    .wallet-balance {
        grid-template-columns: 1fr;
    }

    .wallet-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>
