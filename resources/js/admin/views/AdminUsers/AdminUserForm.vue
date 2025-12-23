<template>
    <div class="admin-user-form">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">{{ isEdit ? 'تعديل مستخدم الإدارة' : 'إضافة مستخدم إدارة' }}</h1>
                <router-link :to="{ name: 'admin.users.index' }" class="btn-back">
                    <i class="bi bi-arrow-right me-2"></i>
                    العودة للقائمة
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
                            <label for="name" class="form-label">الاسم <span class="required">*</span></label>
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
                            <label for="email" class="form-label">البريد الإلكتروني <span class="required">*</span></label>
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
                                كلمة المرور <span v-if="!isEdit" class="required">*</span>
                                <span v-if="isEdit" class="text-muted">(اتركها فارغة للإبقاء على الحالية)</span>
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
                                تأكيد كلمة المرور <span v-if="!isEdit" class="required">*</span>
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
                            <label class="form-label">الصلاحيات</label>
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
                            <label class="form-label">الحالة</label>
                            <div class="status-toggle">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="toggle-input"
                                />
                                <label for="is_active" class="toggle-label">
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-text">{{ form.is_active ? 'نشط' : 'غير نشط' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <router-link :to="{ name: 'admin.users.index' }" class="btn-secondary">
                            إلغاء
                        </router-link>
                        <button type="submit" class="btn-primary" :disabled="submitting">
                            <i v-if="submitting" class="bi bi-arrow-repeat spinner me-2"></i>
                            {{ submitting ? 'جاري الحفظ...' : (isEdit ? 'تحديث المستخدم' : 'إضافة مستخدم') }}
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
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const toast = useToast();

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
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: 'فشل في جلب تفاصيل مستخدم الإدارة',
            life: 3000
        });
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

        toast.add({
            severity: 'success',
            summary: 'نجاح',
            detail: isEdit.value ? 'تم تحديث مستخدم الإدارة بنجاح' : 'تم إنشاء مستخدم الإدارة بنجاح',
            life: 3000
        });

        router.push({ name: 'admin.users.index' });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({
                severity: 'error',
                summary: 'خطأ',
                detail: error.response?.data?.message || 'حدث خطأ أثناء الحفظ',
                life: 3000
            });
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

<style scoped src="../../../../css/adminuserform.css"></style>
