<template>
    <div class="wallet-management">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-wallet2 me-2"></i>
                    Wallet Management
                </h1>
                <p class="page-subtitle">Manage staff wallets, main cashbox, and expense wallets</p>
            </div>
            <div class="header-actions">
                <Button
                    v-if="isAdministrator"
                    label="Create Wallet for User"
                    icon="bi bi-person-plus"
                    @click="showCreateWalletForUserModal = true"
                    severity="primary"
                    class="me-2"
                />
                <!-- <Button
                    label="Create Expense Wallet"
                    icon="bi bi-plus-circle"
                    @click="showCreateExpenseWalletModal = true"
                    severity="success"
                /> -->
            </div>
        </div>

        <!-- Balance Summary Cards -->
        <div v-if="!loadingSummary" class="balance-summary">
            <div class="summary-card staff-card">
                <div class="card-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="card-content">
                    <h3>Total Staff Balance</h3>
                    <p class="amount">${{ formatMoney(summary.total_staff_balance) }}</p>
                    <span class="wallets-count">{{ summary.staff_wallets_count }} Staff Wallets</span>
                </div>
            </div>

            <div class="summary-card cashbox-card">
                <div class="card-icon">
                    <i class="bi bi-bank2"></i>
                </div>
                <div class="card-content">
                    <h3>Main Cashbox</h3>
                    <p class="amount">${{ formatMoney(summary.main_cashbox_balance) }}</p>
                    <span class="wallets-count">Central Safe</span>
                </div>
            </div>

            <div class="summary-card expense-card">
                <div class="card-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="card-content">
                    <h3>Total Expense Balance</h3>
                    <p class="amount">${{ formatMoney(summary.total_expense_balance) }}</p>
                    <span class="wallets-count">{{ summary.expense_wallets_count }} Expense Wallets</span>
                </div>
            </div>

            <div class="summary-card total-card">
                <div class="card-icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="card-content">
                    <h3>Total System Balance</h3>
                    <p class="amount">${{ formatMoney(summary.total_balance) }}</p>
                    <span class="wallets-count">All Wallets</span>
                </div>
            </div>
        </div>

        <TableSkeleton v-if="loadingSummary" :rows="1" :columns="4" />

        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label>Wallet Type</label>
                <Select
                    v-model="filters.type"
                    :options="walletTypes"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All Types"
                    @change="fetchWallets"
                />
            </div>
            <div class="filter-group">
                <label>Search</label>
                <InputText
                    v-model="filters.search"
                    placeholder="Search wallet name..."
                    @input="fetchWallets"
                />
            </div>
        </div>

        <!-- Wallets Table -->
        <div class="data-card">
            <TableSkeleton v-if="loading" :rows="10" :columns="6" />

            <table v-else class="data-table">
                <thead>
                    <tr>
                        <th>Wallet Name</th>
                        <th>Type</th>
                        <th>Owner</th>
                        <th>Receivable</th>
                        <th>Payable</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="wallet in wallets" :key="wallet.id">
                        <td>
                            <div class="wallet-name">
                                <i :class="getWalletIcon(wallet.type)"></i>
                                <span>{{ wallet.name }}</span>
                            </div>
                        </td>
                        <td>
                            <span :class="['type-badge', wallet.type]">
                                {{ formatWalletType(wallet.type) }}
                            </span>
                        </td>
                        <td>
                            <span v-if="wallet.owner">{{ wallet.owner.name }}</span>
                            <span v-else class="text-muted">System</span>
                        </td>
                        <td>
                            <span class="financial-value receivable">
                                ${{ formatMoney(wallet.receivable_amount) }}
                            </span>
                        </td>
                        <td>
                            <span class="financial-value payable">
                                ${{ formatMoney(wallet.payable_amount) }}
                            </span>
                        </td>
                        <td>
                            <span :class="['financial-value', 'balance', wallet.balance >= 0 ? 'positive' : 'negative']">
                                ${{ formatMoney(wallet.balance) }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <Button
                                    icon="bi bi-eye"
                                    severity="info"
                                    text
                                    rounded
                                    @click="viewWallet(wallet.id)"
                                    v-tooltip.top="'View Details'"
                                />
                                <Button
                                    v-if="isAdministrator"
                                    icon="bi bi-pencil-square"
                                    severity="warning"
                                    text
                                    rounded
                                    @click="openEditModal(wallet)"
                                    v-tooltip.top="'Edit Wallet'"
                                />
                                <Button
                                    v-if="wallet.type === 'staff'"
                                    icon="bi bi-arrow-right-circle"
                                    severity="success"
                                    text
                                    rounded
                                    @click="openTransferModal(wallet, 'staff_to_cashbox')"
                                    v-tooltip.top="'Transfer to Main Cashbox'"
                                />
                                <Button
                                    v-if="wallet.type === 'main_cashbox'"
                                    icon="bi bi-arrow-right-circle"
                                    severity="warning"
                                    text
                                    rounded
                                    @click="openTransferModal(wallet, 'cashbox_to_expense')"
                                    v-tooltip.top="'Transfer to Expense Wallet'"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!loading && wallets.length === 0" class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No wallets found</p>
            </div>
        </div>

        <!-- Transfer Modal -->
        <Dialog
            v-model:visible="showTransferModal"
            modal
            :header="transferModalTitle"
            :style="{ width: '600px' }"
        >
            <div class="transfer-form">
                <div class="form-group">
                    <label>
                        <i class="bi bi-wallet me-2"></i>
                        From Wallet
                    </label>
                    <InputText
                        :value="transferData.fromWallet?.name"
                        disabled
                    />
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-wallet2 me-2"></i>
                        To Wallet
                        <span class="text-danger">*</span>
                    </label>
                    <Select
                        v-model="transferData.toWalletId"
                        :options="transferDestinations"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select destination wallet"
                        :class="{ 'p-invalid': transferErrors.to_wallet_id }"
                    />
                    <small v-if="transferErrors.to_wallet_id" class="error-message">
                        {{ transferErrors.to_wallet_id[0] }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-cash me-2"></i>
                        Amount
                        <span class="text-danger">*</span>
                    </label>
                    <InputNumber
                        v-model="transferData.amount"
                        mode="currency"
                        currency="USD"
                        placeholder="Enter amount"
                        :class="{ 'p-invalid': transferErrors.amount }"
                    />
                    <small v-if="transferErrors.amount" class="error-message">
                        {{ transferErrors.amount[0] }}
                    </small>
                    <small class="hint-text">
                        Available balance: ${{ formatMoney(transferData.fromWallet?.balance || 0) }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-chat-left-text me-2"></i>
                        Note
                    </label>
                    <Textarea
                        v-model="transferData.note"
                        rows="3"
                        placeholder="Optional note for this transfer"
                    />
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showTransferModal = false"
                    :disabled="submittingTransfer"
                />
                <Button
                    label="Transfer"
                    icon="bi bi-arrow-right-circle"
                    @click="submitTransfer"
                    :loading="submittingTransfer"
                />
            </template>
        </Dialog>

        <!-- Create Expense Wallet Modal -->
        <Dialog
            v-model:visible="showCreateExpenseWalletModal"
            modal
            header="Create New Expense Wallet"
            :style="{ width: '500px' }"
        >
            <div class="expense-wallet-form">
                <div class="form-group">
                    <label>
                        <i class="bi bi-tag me-2"></i>
                        Wallet Name
                        <span class="text-danger">*</span>
                    </label>
                    <InputText
                        v-model="newExpenseWallet.name"
                        placeholder="e.g., Office Supplies"
                        :class="{ 'p-invalid': expenseWalletErrors.name }"
                    />
                    <small v-if="expenseWalletErrors.name" class="error-message">
                        {{ expenseWalletErrors.name[0] }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-person me-2"></i>
                        Assign to Admin (Optional)
                    </label>
                    <Select
                        v-model="newExpenseWallet.ownerId"
                        :options="admins"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select admin (optional)"
                        showClear
                    />
                    <small class="hint-text">
                        Leave empty for system-wide expense wallet
                    </small>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showCreateExpenseWalletModal = false"
                    :disabled="creatingExpenseWallet"
                />
                <Button
                    label="Create Wallet"
                    icon="bi bi-plus-circle"
                    @click="createExpenseWallet"
                    :loading="creatingExpenseWallet"
                />
            </template>
        </Dialog>

        <!-- Create Wallet for User Modal -->
        <Dialog
            v-model:visible="showCreateWalletForUserModal"
            modal
            header="Create Wallet for User"
            :style="{ width: '500px' }"
        >
            <div class="wallet-for-user-form">
                <div class="form-group">
                    <label>
                        <i class="bi bi-person me-2"></i>
                        Select User
                        <span class="text-danger">*</span>
                    </label>
                    <Select
                        v-model="newWalletForUser.adminId"
                        :options="admins"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select a user"
                        :class="{ 'p-invalid': walletForUserErrors.admin_id }"
                        filter
                    />
                    <small v-if="walletForUserErrors.admin_id" class="error-message">
                        {{ walletForUserErrors.admin_id[0] }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-tag me-2"></i>
                        Wallet Name
                        <span class="text-danger">*</span>
                    </label>
                    <InputText
                        v-model="newWalletForUser.name"
                        placeholder="e.g., John's Main Wallet or Marketing Expenses"
                        :class="{ 'p-invalid': walletForUserErrors.name }"
                    />
                    <small v-if="walletForUserErrors.name" class="error-message">
                        {{ walletForUserErrors.name[0] }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-wallet me-2"></i>
                        Wallet Type
                        <span class="text-danger">*</span>
                    </label>
                    <Select
                        v-model="newWalletForUser.type"
                        :options="userWalletTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select wallet type"
                        :class="{ 'p-invalid': walletForUserErrors.type }"
                    />
                    <small v-if="walletForUserErrors.type" class="error-message">
                        {{ walletForUserErrors.type[0] }}
                    </small>
                    <small class="hint-text">
                        <strong>Staff Wallet:</strong> For receiving payments and general use<br>
                        <strong>Expense Wallet:</strong> Specifically for managing expenses only
                    </small>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showCreateWalletForUserModal = false"
                    :disabled="creatingWalletForUser"
                />
                <Button
                    label="Create Wallet"
                    icon="bi bi-plus-circle"
                    @click="createWalletForUser"
                    :loading="creatingWalletForUser"
                />
            </template>
        </Dialog>

        <!-- Edit Wallet Modal -->
        <Dialog
            v-model:visible="showEditModal"
            modal
            header="Adjust Wallet Balance"
            :style="{ width: '600px' }"
        >
            <div class="edit-wallet-form">
                <div class="info-banner">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>
                        <strong>Balance Adjustment</strong>
                        <p>Adjusting receivable/payable creates proper audit trail transactions. Balance = Receivable - Payable</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-wallet me-2"></i>
                        Wallet Name
                        <span class="text-danger">*</span>
                    </label>
                    <InputText
                        v-model="editData.name"
                        placeholder="Enter wallet name"
                        :class="{ 'p-invalid': editErrors.name }"
                    />
                    <small v-if="editErrors.name" class="error-message">
                        {{ editErrors.name[0] }}
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-arrow-down-circle me-2"></i>
                        Receivable Amount (Money IN)
                        <span class="text-danger">*</span>
                    </label>
                    <InputNumber
                        v-model="editData.receivable_amount"
                        mode="currency"
                        currency="USD"
                        placeholder="Enter receivable amount"
                        :class="{ 'p-invalid': editErrors.receivable_amount }"
                    />
                    <small v-if="editErrors.receivable_amount" class="error-message">
                        {{ editErrors.receivable_amount[0] }}
                    </small>
                    <small class="hint-text">
                        Current: ${{ formatMoney(editData.original_receivable || 0) }} | Credits, income, deposits
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        Payable Amount (Money OUT)
                        <span class="text-danger">*</span>
                    </label>
                    <InputNumber
                        v-model="editData.payable_amount"
                        mode="currency"
                        currency="USD"
                        placeholder="Enter payable amount"
                        :class="{ 'p-invalid': editErrors.payable_amount }"
                    />
                    <small v-if="editErrors.payable_amount" class="error-message">
                        {{ editErrors.payable_amount[0] }}
                    </small>
                    <small class="hint-text">
                        Current: ${{ formatMoney(editData.original_payable || 0) }} | Debits, expenses, withdrawals
                    </small>
                </div>

                <div class="balance-preview">
                    <div class="preview-label">New Balance:</div>
                    <div :class="['preview-value', newBalance >= 0 ? 'positive' : 'negative']">
                        ${{ formatMoney(newBalance) }}
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showEditModal = false"
                    :disabled="updatingWallet"
                />
                <Button
                    label="Update Wallet"
                    icon="bi bi-check-circle"
                    @click="submitWalletUpdate"
                    :loading="updatingWallet"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

const loading = ref(false);
const loadingSummary = ref(false);
const wallets = ref([]);
const summary = ref({
    total_staff_balance: 0,
    staff_wallets_count: 0,
    main_cashbox_balance: 0,
    total_expense_balance: 0,
    expense_wallets_count: 0,
    total_balance: 0,
});

const filters = ref({
    type: null,
    search: '',
});

const walletTypes = [
    { label: 'All Types', value: null },
    { label: 'Staff Wallet', value: 'staff' },
    { label: 'Main Cashbox', value: 'main_cashbox' },
    { label: 'Expense Wallet', value: 'expense' },
];

// Transfer Modal
const showTransferModal = ref(false);
const submittingTransfer = ref(false);
const transferData = ref({
    fromWallet: null,
    toWalletId: null,
    amount: null,
    note: '',
    transferType: '',
});
const transferErrors = ref({});
const transferDestinations = ref([]);

// Create Expense Wallet Modal
const showCreateExpenseWalletModal = ref(false);
const creatingExpenseWallet = ref(false);
const newExpenseWallet = ref({
    name: '',
    ownerId: null,
});
const expenseWalletErrors = ref({});
const admins = ref([]);

// Create Wallet for User Modal
const showCreateWalletForUserModal = ref(false);
const creatingWalletForUser = ref(false);
const newWalletForUser = ref({
    adminId: null,
    name: '',
    type: 'staff',
});
const walletForUserErrors = ref({});
const userWalletTypes = [
    { label: 'Staff Wallet', value: 'staff' },
    { label: 'Expense Wallet', value: 'expense' },
];

// Edit Wallet Modal
const showEditModal = ref(false);
const updatingWallet = ref(false);
const editData = ref({
    id: null,
    name: '',
    receivable_amount: 0,
    payable_amount: 0,
    original_receivable: 0,
    original_payable: 0,
});
const editErrors = ref({});

// Admin user check
const adminUser = ref(null);

const transferModalTitle = computed(() => {
    if (transferData.value.transferType === 'staff_to_cashbox') {
        return 'Transfer to Main Cashbox';
    } else if (transferData.value.transferType === 'cashbox_to_expense') {
        return 'Transfer to Expense Wallet';
    }
    return 'Transfer Money';
});

const isAdministrator = computed(() => {
    return adminUser.value?.roles?.includes('Administrator') || false;
});

const newBalance = computed(() => {
    const receivable = parseFloat(editData.value.receivable_amount || 0);
    const payable = parseFloat(editData.value.payable_amount || 0);
    return receivable - payable;
});

onMounted(() => {
    const storedUser = localStorage.getItem('admin_user');
    if (storedUser) {
        adminUser.value = JSON.parse(storedUser);
    }
    fetchBalanceSummary();
    fetchWallets();
    fetchAdmins();
});

const fetchBalanceSummary = async () => {
    loadingSummary.value = true;
    try {
        const response = await axios.get('/admin/wallets/balance-summary');
        if (response.data.success) {
            summary.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching balance summary:', error);
    } finally {
        loadingSummary.value = false;
    }
};

const fetchWallets = async () => {
    loading.value = true;
    try {
        const params = {};
        if (filters.value.type) params.type = filters.value.type;
        if (filters.value.search) params.search = filters.value.search;

        const response = await axios.get('/admin/wallets', { params });
        if (response.data.success) {
            wallets.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching wallets:', error);
    } finally {
        loading.value = false;
    }
};

const fetchAdmins = async () => {
    try {
        const response = await axios.get('/admin/wallets/admins-list');
        if (response.data.success) {
            admins.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching admins:', error);
    }
};

const viewWallet = (walletId) => {
    router.push({ name: 'wallet-details', params: { id: walletId } });
};

const openTransferModal = async (wallet, transferType) => {
    transferData.value = {
        fromWallet: wallet,
        toWalletId: null,
        amount: null,
        note: '',
        transferType: transferType,
    };
    transferErrors.value = {};

    // Fetch available destination wallets
    try {
        if (transferType === 'staff_to_cashbox') {
            // Get main cashbox
            const response = await axios.get('/admin/wallets', { params: { type: 'main_cashbox' } });
            if (response.data.success) {
                transferDestinations.value = response.data.data;
            }
        } else if (transferType === 'cashbox_to_expense') {
            // Get expense wallets
            const response = await axios.get('/admin/wallets', { params: { type: 'expense' } });
            if (response.data.success) {
                transferDestinations.value = response.data.data;
            }
        }
    } catch (error) {
        console.error('Error fetching transfer destinations:', error);
    }

    showTransferModal.value = true;
};

const submitTransfer = async () => {
    submittingTransfer.value = true;
    transferErrors.value = {};

    try {
        const payload = {
            from_wallet_id: transferData.value.fromWallet.id,
            to_wallet_id: transferData.value.toWalletId,
            amount: transferData.value.amount,
            note: transferData.value.note,
        };

        const response = await axios.post('/admin/wallets/transfer', payload);

        if (response.data.success) {
            showTransferModal.value = false;
            await fetchBalanceSummary();
            await fetchWallets();
            // Could add a success toast here
        }
    } catch (error) {
        if (error.response?.status === 422) {
            transferErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error transferring money:', error);
        }
    } finally {
        submittingTransfer.value = false;
    }
};

const createExpenseWallet = async () => {
    creatingExpenseWallet.value = true;
    expenseWalletErrors.value = {};

    try {
        const payload = {
            name: newExpenseWallet.value.name,
            owner_id: newExpenseWallet.value.ownerId,
        };

        const response = await axios.post('/admin/wallets/expense-wallets', payload);

        if (response.data.success) {
            showCreateExpenseWalletModal.value = false;
            newExpenseWallet.value = { name: '', ownerId: null };
            await fetchBalanceSummary();
            await fetchWallets();
            // Could add a success toast here
        }
    } catch (error) {
        if (error.response?.status === 422) {
            expenseWalletErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error creating expense wallet:', error);
        }
    } finally {
        creatingExpenseWallet.value = false;
    }
};

const createWalletForUser = async () => {
    creatingWalletForUser.value = true;
    walletForUserErrors.value = {};

    try {
        const payload = {
            admin_id: newWalletForUser.value.adminId,
            name: newWalletForUser.value.name,
            type: newWalletForUser.value.type,
        };

        const response = await axios.post('/admin/wallets/create-for-user', payload);

        if (response.data.success) {
            showCreateWalletForUserModal.value = false;
            newWalletForUser.value = { adminId: null, name: '', type: 'staff' };
            await fetchBalanceSummary();
            await fetchWallets();
            // Could add a success toast here
        }
    } catch (error) {
        if (error.response?.status === 422) {
            walletForUserErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error creating wallet for user:', error);
        }
    } finally {
        creatingWalletForUser.value = false;
    }
};

const openEditModal = (wallet) => {
    editData.value = {
        id: wallet.id,
        name: wallet.name,
        receivable_amount: parseFloat(wallet.receivable_amount),
        payable_amount: parseFloat(wallet.payable_amount),
        original_receivable: wallet.receivable_amount,
        original_payable: wallet.payable_amount,
    };
    editErrors.value = {};
    showEditModal.value = true;
};

const submitWalletUpdate = async () => {
    updatingWallet.value = true;
    editErrors.value = {};

    try {
        const payload = {
            name: editData.value.name,
            receivable_amount: editData.value.receivable_amount,
            payable_amount: editData.value.payable_amount,
        };

        const response = await axios.put(`/admin/wallets/${editData.value.id}`, payload);

        if (response.data.success) {
            showEditModal.value = false;
            await fetchBalanceSummary();
            await fetchWallets();
            // Could add a success toast here
        }
    } catch (error) {
        if (error.response?.status === 422) {
            editErrors.value = error.response.data.errors || {};
        } else {
            console.error('Error updating wallet:', error);
        }
    } finally {
        updatingWallet.value = false;
    }
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const formatWalletType = (type) => {
    const types = {
        'staff': 'Staff Wallet',
        'main_cashbox': 'Main Cashbox',
        'expense': 'Expense Wallet',
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
</script>

<style scoped>
.wallet-management {
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

/* Balance Summary Cards */
.balance-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.staff-card .card-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.cashbox-card .card-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.expense-card .card-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.total-card .card-icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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

.wallets-count {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Filters */
.filters-section {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-group {
    flex: 1;
}

.filter-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
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

.wallet-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #1e293b;
}

.wallet-name i {
    font-size: 1.2rem;
    color: #8b5cf6;
}

.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.type-badge.staff {
    background: #dbeafe;
    color: #1e40af;
}

.type-badge.main_cashbox {
    background: #d1fae5;
    color: #065f46;
}

.type-badge.expense {
    background: #fed7aa;
    color: #92400e;
}

.financial-value {
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.financial-value.receivable {
    color: #10b981;
    background: #d1fae5;
}

.financial-value.payable {
    color: #f59e0b;
    background: #fed7aa;
}

.financial-value.balance.positive {
    color: #10b981;
    background: #d1fae5;
}

.financial-value.balance.negative {
    color: #ef4444;
    background: #fee2e2;
}

.text-muted {
    color: #94a3b8;
    font-style: italic;
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

/* Modal Styles */
.transfer-form,
.expense-wallet-form,
.edit-wallet-form {
    padding: 1rem 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.form-group .text-danger {
    color: #ef4444;
}

.error-message {
    display: block;
    color: #ef4444;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.hint-text {
    display: block;
    color: #64748b;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.p-invalid {
    border-color: #ef4444 !important;
}

/* Balance Preview */
.balance-preview {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 2px solid #e2e8f0;
}

.preview-label {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.preview-value {
    font-size: 1.75rem;
    font-weight: 700;
    padding: 0.5rem 1rem;
    border-radius: 8px;
}

.preview-value.positive {
    color: #10b981;
    background: #d1fae5;
}

.preview-value.negative {
    color: #ef4444;
    background: #fee2e2;
}

/* Info Banner */
.info-banner {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-left: 4px solid #3b82f6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.info-banner i {
    color: #3b82f6;
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.info-banner strong {
    display: block;
    color: #1e40af;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.info-banner p {
    color: #1e40af;
    font-size: 0.813rem;
    margin: 0;
    line-height: 1.4;
}
</style>
