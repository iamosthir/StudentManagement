<template>
    <div class="expense-categories">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-tags me-2"></i>
                    Expense Categories
                </h1>
                <p class="page-subtitle">Manage expense categories for better organization</p>
            </div>
            <Button
                label="Add Category"
                icon="bi bi-plus-circle"
                @click="openCreateModal"
            />
        </div>

        <!-- Categories List -->
        <div class="data-card">
            <TableSkeleton v-if="loading" :rows="5" :columns="3" />

            <table v-else class="data-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Expenses Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="category in categories" :key="category.id">
                        <td>
                            <div class="category-name">
                                <i class="bi bi-tag-fill"></i>
                                {{ category.name }}
                            </div>
                        </td>
                        <td>
                            <span class="badge">{{ category.expenses_count || 0 }} expenses</span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <Button
                                    icon="bi bi-pencil"
                                    severity="info"
                                    text
                                    rounded
                                    @click="openEditModal(category)"
                                    v-tooltip.top="'Edit'"
                                />
                                <Button
                                    icon="bi bi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    @click="confirmDelete(category)"
                                    v-tooltip.top="'Delete'"
                                    :disabled="category.expenses_count > 0"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!loading && categories.length === 0" class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No expense categories found</p>
                <Button label="Add First Category" @click="openCreateModal" />
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog
            v-model:visible="showModal"
            modal
            :header="isEditing ? 'Edit Category' : 'Add Category'"
            :style="{ width: '500px' }"
        >
            <div class="category-form">
                <div class="form-group">
                    <label>
                        <i class="bi bi-tag me-2"></i>
                        Category Name
                        <span class="text-danger">*</span>
                    </label>
                    <InputText
                        v-model="form.name"
                        placeholder="e.g., Office Supplies, Utilities"
                        :class="{ 'p-invalid': errors.name }"
                    />
                    <small v-if="errors.name" class="error-message">
                        {{ errors.name[0] }}
                    </small>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showModal = false"
                    :disabled="submitting"
                />
                <Button
                    :label="isEditing ? 'Update' : 'Create'"
                    @click="submitForm"
                    :loading="submitting"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import TableSkeleton from '@/components/TableSkeleton.vue';

const confirm = useConfirm();
const toast = useToast();

const loading = ref(false);
const categories = ref([]);
const showModal = ref(false);
const isEditing = ref(false);
const submitting = ref(false);
const currentCategory = ref(null);

const form = ref({
    name: '',
});

const errors = ref({});

onMounted(() => {
    fetchCategories();
});

const fetchCategories = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/admin/expense-categories');
        if (response.data.success) {
            categories.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching categories:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    currentCategory.value = null;
    form.value = { name: '' };
    errors.value = {};
    showModal.value = true;
};

const openEditModal = (category) => {
    isEditing.value = true;
    currentCategory.value = category;
    form.value = { name: category.name };
    errors.value = {};
    showModal.value = true;
};

const submitForm = async () => {
    errors.value = {};
    submitting.value = true;

    try {
        if (isEditing.value) {
            const response = await axios.put(`/admin/expense-categories/${currentCategory.value.id}`, form.value);
            if (response.data.success) {
                showModal.value = false;
                await fetchCategories();
            }
        } else {
            const response = await axios.post('/admin/expense-categories', form.value);
            if (response.data.success) {
                showModal.value = false;
                await fetchCategories();
            }
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'An error occurred',
                life: 3000
            });
        }
    } finally {
        submitting.value = false;
    }
};

const confirmDelete = (category) => {
    confirm.require({
        message: `Are you sure you want to delete "${category.name}"?`,
        header: 'Confirm Delete',
        icon: 'bi bi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => deleteCategory(category),
    });
};

const deleteCategory = async (category) => {
    try {
        const response = await axios.delete(`/admin/expense-categories/${category.id}`);
        if (response.data.success) {
            await fetchCategories();
        }
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to delete category',
            life: 3000
        });
    }
};
</script>

<style scoped>
.expense-categories {
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

.category-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #1e293b;
}

.category-name i {
    color: #6366f1;
    font-size: 1.2rem;
}

.badge {
    background: #e0e7ff;
    color: #3730a3;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
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

.category-form {
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

.text-danger {
    color: #ef4444;
}

.error-message {
    display: block;
    color: #ef4444;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.p-invalid {
    border-color: #ef4444 !important;
}
</style>
