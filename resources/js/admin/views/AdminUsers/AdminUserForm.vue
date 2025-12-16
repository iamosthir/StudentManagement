<template>
    <div class="admin-user-form">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">{{ isEdit ? 'Edit Admin User' : 'Create Admin User' }}</h1>
                <router-link :to="{ name: 'admin.users.index' }" class="btn-back">
                    <i class="bi bi-arrow-right me-2"></i>
                    Back to List
                </router-link>
            </div>
        </div>

        <!-- Form Card -->
        <div class="glass-card">
            <div class="glass-card-body">
                <form @submit.prevent="submitForm">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Name <span class="required">*</span></label>
                            <input
                                type="text"
                                id="name"
                                v-model="form.name"
                                class="form-input"
                                :class="{ 'is-invalid': errors.name }"
                                required
                            />
                            <div v-if="errors.name" class="invalid-feedback">
                                {{ errors.name[0] }}
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="required">*</span></label>
                            <input
                                type="email"
                                id="email"
                                v-model="form.email"
                                class="form-input"
                                :class="{ 'is-invalid': errors.email }"
                                required
                            />
                            <div v-if="errors.email" class="invalid-feedback">
                                {{ errors.email[0] }}
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Password <span v-if="!isEdit" class="required">*</span>
                                <span v-if="isEdit" class="text-muted">(leave blank to keep current)</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                v-model="form.password"
                                class="form-input"
                                :class="{ 'is-invalid': errors.password }"
                                :required="!isEdit"
                            />
                            <div v-if="errors.password" class="invalid-feedback">
                                {{ errors.password[0] }}
                            </div>
                        </div>

                        <!-- Password Confirmation -->
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirm Password <span v-if="!isEdit" class="required">*</span>
                            </label>
                            <input
                                type="password"
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                class="form-input"
                                :required="!isEdit || form.password"
                            />
                        </div>

                        <!-- Roles -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Roles</label>
                            <div class="roles-checkboxes">
                                <div v-for="role in availableRoles" :key="role.id" class="role-checkbox">
                                    <input
                                        type="checkbox"
                                        :id="'role-' + role.id"
                                        :value="role.name"
                                        v-model="form.roles"
                                    />
                                    <label :for="'role-' + role.id">{{ role.name }}</label>
                                </div>
                            </div>
                            <div v-if="errors.roles" class="invalid-feedback d-block">
                                {{ errors.roles[0] }}
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div class="status-toggle">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="toggle-input"
                                />
                                <label for="is_active" class="toggle-label">
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-text">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <router-link :to="{ name: 'admin.users.index' }" class="btn-secondary">
                            Cancel
                        </router-link>
                        <button type="submit" class="btn-primary" :disabled="submitting">
                            <i v-if="submitting" class="bi bi-arrow-repeat spinner me-2"></i>
                            {{ submitting ? 'Saving...' : (isEdit ? 'Update Admin' : 'Create Admin') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();

const isEdit = computed(() => !!route.params.id);

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
    is_active: true,
});

const availableRoles = ref([]);
const errors = ref({});
const submitting = ref(false);

// Fetch available roles
const fetchRoles = async () => {
    try {
        const response = await axios.get('/admin/roles');
        availableRoles.value = response.data.roles;
    } catch (error) {
        console.error('Error fetching roles:', error);
    }
};

// Fetch admin data if editing
const fetchAdmin = async () => {
    try {
        const response = await axios.get(`/admin/admin-users/${route.params.id}`);
        const admin = response.data.admin;

        form.value = {
            name: admin.name,
            email: admin.email,
            password: '',
            password_confirmation: '',
            roles: admin.roles.map(role => role.name),
            is_active: admin.is_active,
        };
    } catch (error) {
        console.error('Error fetching admin:', error);
        router.push({ name: 'admin.users.index' });
    }
};

// Submit form
const submitForm = async () => {
    submitting.value = true;
    errors.value = {};

    try {
        const url = isEdit.value
            ? `/admin/admin-users/${route.params.id}`
            : '/admin/admin-users';

        const method = isEdit.value ? 'put' : 'post';

        await axios[method](url, form.value);

        router.push({ name: 'admin.users.index' });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            alert(error.response?.data?.message || 'An error occurred while saving');
        }
    } finally {
        submitting.value = false;
    }
};

onMounted(async () => {
    await fetchRoles();
    if (isEdit.value) {
        await fetchAdmin();
    }
});
</script>

<style scoped>
.admin-user-form {
    padding-bottom: 2rem;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
}

.btn-back {
    height: 40px;
    padding: 0 1.25rem;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    background: white;
    color: #4a5568;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-back:hover {
    background: rgba(0, 0, 0, 0.05);
}

/* Glass Card */
.glass-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.glass-card-body {
    padding: 2rem;
}

/* Form Styles */
.form-label {
    display: block;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.required {
    color: #e53e3e;
}

.form-input {
    width: 100%;
    height: 45px;
    padding: 0 1rem;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.9);
    font-size: 0.9375rem;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input.is-invalid {
    border-color: #e53e3e;
}

.invalid-feedback {
    display: block;
    color: #e53e3e;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

/* Roles Checkboxes */
.roles-checkboxes {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.role-checkbox {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.role-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 2px solid rgba(0, 0, 0, 0.2);
    cursor: pointer;
}

.role-checkbox label {
    font-size: 0.9375rem;
    color: #4a5568;
    cursor: pointer;
    margin: 0;
}

/* Status Toggle */
.status-toggle {
    display: flex;
    align-items: center;
}

.toggle-input {
    display: none;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
    background: #cbd5e0;
    border-radius: 13px;
    transition: all 0.3s;
}

.toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s;
}

.toggle-input:checked + .toggle-label .toggle-switch {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.toggle-input:checked + .toggle-label .toggle-switch::after {
    left: 27px;
}

.toggle-text {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #4a5568;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.btn-secondary {
    height: 45px;
    padding: 0 1.5rem;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    background: white;
    color: #4a5568;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: rgba(0, 0, 0, 0.05);
}

.btn-primary {
    height: 45px;
    padding: 0 1.5rem;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 767px) {
    .glass-card-body {
        padding: 1.5rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn-secondary,
    .form-actions .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>
