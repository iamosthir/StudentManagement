<template>
    <div class="expense-form-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="bi bi-cash-stack gradient-icon"></i>
                    {{ isEditing ? 'Edit Expense' : 'Add New Expense' }}
                </h1>
                <p class="page-subtitle">{{ isEditing ? 'Update expense details' : 'Record a new expense transaction' }}</p>
            </div>
            <Button
                label="Back to Expenses"
                icon="bi bi-arrow-left"
                @click="router.push('/expenses')"
                severity="secondary"
                outlined
            />
        </div>

        <!-- Expense Form -->
        <form @submit.prevent="submitForm" class="expense-form">
            <!-- Basic Info Section -->
            <div class="form-section" :class="{ 'section-animate': formLoaded }">
                <div class="section-header">
                    <div class="section-icon primary">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div>
                        <h3 class="section-title">Expense Details</h3>
                        <p class="section-subtitle">Enter the expense information</p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="wallet_id">
                                <i class="bi bi-wallet2"></i>
                                Expense Wallet <span class="required">*</span>
                            </label>
                            <Select
                                v-model="form.wallet_id"
                                :options="wallets"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select expense wallet"
                                :class="{ 'p-invalid': errors.wallet_id }"
                                :disabled="isEditing"
                            >
                                <template #option="slotProps">
                                    <div class="flex flex-column">
                                        <div class="font-medium">{{ slotProps.option.name }}</div>
                                        <div class="text-sm text-green-600">Balance: ${{ formatMoney(slotProps.option.balance) }}</div>
                                    </div>
                                </template>
                            </Select>
                            <small v-if="errors.wallet_id" class="error-message">
                                {{ errors.wallet_id[0] }}
                            </small>
                            <small v-else-if="selectedWallet" class="hint-text">
                                Available balance: <strong>${{ formatMoney(selectedWallet.balance) }}</strong>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="expense_category_id">
                                <i class="bi bi-tag"></i>
                                Category <span class="required">*</span>
                            </label>
                            <Select
                                v-model="form.expense_category_id"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select category"
                                :class="{ 'p-invalid': errors.expense_category_id }"
                            />
                            <small v-if="errors.expense_category_id" class="error-message">
                                {{ errors.expense_category_id[0] }}
                            </small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">
                                <i class="bi bi-currency-dollar"></i>
                                Amount <span class="required">*</span>
                            </label>
                            <InputNumber
                                v-model="form.amount"
                                inputId="amount"
                                :minFractionDigits="2"
                                :maxFractionDigits="2"
                                :min="0.01"
                                placeholder="0.00"
                                :class="{ 'p-invalid': errors.amount }"
                                :disabled="isEditing"
                            />
                            <small v-if="errors.amount" class="error-message">
                                {{ errors.amount[0] }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="date">
                                <i class="bi bi-calendar"></i>
                                Date <span class="required">*</span>
                            </label>
                            <DatePicker
                                v-model="form.date"
                                dateFormat="yy-mm-dd"
                                placeholder="Select date"
                                showIcon
                                :class="{ 'p-invalid': errors.date }"
                            />
                            <small v-if="errors.date" class="error-message">
                                {{ errors.date[0] }}
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">
                            <i class="bi bi-card-text"></i>
                            Description
                        </label>
                        <Textarea
                            v-model="form.description"
                            inputId="description"
                            rows="4"
                            placeholder="Enter expense description..."
                            :class="{ 'p-invalid': errors.description }"
                        />
                        <small v-if="errors.description" class="error-message">
                            {{ errors.description[0] }}
                        </small>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    outlined
                    @click="router.push('/expenses')"
                    :disabled="submitting"
                />
                <Button
                    type="submit"
                    :label="isEditing ? 'Update Expense' : 'Create Expense'"
                    icon="bi bi-check-circle"
                    iconPos="right"
                    :loading="submitting"
                />
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const isEditing = computed(() => !!route.params.id);

const form = ref({
    wallet_id: null,
    expense_category_id: null,
    amount: null,
    date: new Date(),
    description: '',
});

const errors = ref({});
const submitting = ref(false);
const formLoaded = ref(false);
const wallets = ref([]);
const categories = ref([]);

const selectedWallet = computed(() => {
    return wallets.value.find(w => w.id === form.value.wallet_id);
});

onMounted(async () => {
    setTimeout(() => {
        formLoaded.value = true;
    }, 100);

    await fetchWallets();
    await fetchCategories();

    if (isEditing.value) {
        await fetchExpense();
    }
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

const fetchExpense = async () => {
    try {
        const response = await axios.get(`/admin/expenses/${route.params.id}`);
        if (response.data.success) {
            const expense = response.data.data;
            form.value = {
                wallet_id: expense.wallet_id,
                expense_category_id: expense.expense_category_id,
                amount: expense.amount,
                date: new Date(expense.date),
                description: expense.description || '',
            };
        }
    } catch (error) {
        console.error('Error fetching expense:', error);
        router.push('/expenses');
    }
};

const submitForm = async () => {
    errors.value = {};
    submitting.value = true;

    try {
        const payload = {
            ...form.value,
            date: formatDateForApi(form.value.date),
        };

        let response;
        if (isEditing.value) {
            response = await axios.put(`/admin/expenses/${route.params.id}`, payload);
        } else {
            response = await axios.post('/admin/expenses', payload);
        }

        if (response.data.success) {
            router.push('/expenses');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            alert(error.response?.data?.message || 'An error occurred');
        }
    } finally {
        submitting.value = false;
    }
};

const formatMoney = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const formatDateForApi = (date) => {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
</script>

<style scoped>
.expense-form-container {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.header-content {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.gradient-icon {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.page-subtitle {
    color: #64748b;
    margin: 0;
}

.expense-form {
    background: white;
    border-radius: 16px;
}

.form-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    opacity: 0;
    transform: translateY(20px);
}

.form-section.section-animate {
    opacity: 1;
    transform: translateY(0);
}

.form-section:hover {
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
    transform: translateY(-4px);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.section-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    animation: float 3s ease-in-out infinite;
}

.section-icon.primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.section-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0 0;
}

.section-body {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #334155;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group label i {
    color: #6366f1;
}

.required {
    color: #ef4444;
}

.error-message {
    color: #ef4444;
    font-size: 0.875rem;
}

.hint-text {
    color: #64748b;
    font-size: 0.875rem;
}

.p-invalid {
    border-color: #ef4444 !important;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 2rem;
    background: #f8fafc;
    border-radius: 0 0 16px 16px;
    margin-top: -1.5rem;
}

@media (max-width: 768px) {
    .expense-form-container {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
