<template>
    <div class="expense-form-container" dir="rtl">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="bi bi-cash-stack gradient-icon"></i>
                    {{ isEditing ? 'تعديل مصروف' : 'إضافة مصروف جديد' }}
                </h1>
                <p class="page-subtitle">{{ isEditing ? 'تحديث تفاصيل المصروف' : 'تسجيل معاملة مصروف جديدة' }}</p>
            </div>
            <Button
                label="العودة للمصروفات"
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
                        <h3 class="section-title">تفاصيل المصروف</h3>
                        <p class="section-subtitle">أدخل معلومات المصروف</p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="wallet_id">
                                <i class="bi bi-wallet2"></i>
                                محفظة المصروف <span class="required">*</span>
                            </label>
                            <Select
                                v-model="form.wallet_id"
                                :options="wallets"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="اختر محفظة المصروف"
                                :class="{ 'p-invalid': errors.wallet_id }"
                                :disabled="isEditing"
                            >
                                <template #option="slotProps">
                                    <div class="flex flex-column">
                                        <div class="font-medium">{{ slotProps.option.name }}</div>
                                        <div class="text-sm text-green-600">الرصيد: ${{ formatMoney(slotProps.option.balance) }}</div>
                                    </div>
                                </template>
                            </Select>
                            <small v-if="errors.wallet_id" class="error-message">
                                {{ errors.wallet_id[0] }}
                            </small>
                            <small v-else-if="selectedWallet" class="hint-text">
                                الرصيد المتاح: <strong>${{ formatMoney(selectedWallet.balance) }}</strong>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="expense_category_id">
                                <i class="bi bi-tag"></i>
                                الفئة <span class="required">*</span>
                            </label>
                            <Select
                                v-model="form.expense_category_id"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="اختر الفئة"
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
                                المبلغ <span class="required">*</span>
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
                                التاريخ <span class="required">*</span>
                            </label>
                            <DatePicker
                                v-model="form.date"
                                dateFormat="yy-mm-dd"
                                placeholder="اختر التاريخ"
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
                            الوصف
                        </label>
                        <Textarea
                            v-model="form.description"
                            inputId="description"
                            rows="4"
                            placeholder="أدخل وصف المصروف..."
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
                    label="إلغاء"
                    severity="secondary"
                    outlined
                    @click="router.push('/expenses')"
                    :disabled="submitting"
                />
                <Button
                    type="submit"
                    :label="isEditing ? 'تحديث المصروف' : 'إنشاء المصروف'"
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
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();
const toast = useToast();

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
            toast.add({
                severity: 'error',
                summary: 'خطأ',
                detail: error.response?.data?.message || 'حدث خطأ',
                life: 3000
            });
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

<style scoped src="../../../../css/expense.css"></style>
