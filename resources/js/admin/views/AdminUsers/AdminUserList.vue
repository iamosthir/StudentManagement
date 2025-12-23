<template>
    <div class="admin-users">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h1 class="page-title">مستخدمو الإدارة</h1>
                <router-link :to="{ name: 'admin.users.create' }" class="btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>
                    إضافة مستخدم
                </router-link>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="glass-card mb-4">
            <div class="glass-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                v-model="search"
                                @input="searchAdmins"
                                placeholder="البحث بالاسم أو البريد الإلكتروني..."
                                class="search-input"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Users Table -->
        <div class="glass-card">
            <div class="glass-card-body">
                <div v-if="loading" class="loading-state">
                    <i class="bi bi-arrow-repeat spinner"></i>
                    <p>جاري تحميل مستخدمي الإدارة...</p>
                </div>

                <div v-else-if="admins.length === 0" class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>لم يتم العثور على مستخدمين</p>
                </div>

                <div v-else class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الصلاحيات</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="admin in admins" :key="admin.id">
                                <td class="fw-medium">{{ admin.name }}</td>
                                <td>{{ admin.email }}</td>
                                <td>
                                    <span
                                        v-for="role in admin.roles"
                                        :key="role.id"
                                        class="role-badge"
                                    >
                                        {{ role.name }}
                                    </span>
                                    <span v-if="!admin.roles || admin.roles.length === 0" class="text-muted">
                                        لا توجد صلاحيات
                                    </span>
                                </td>
                                <td>
                                    <span :class="['status-badge', admin.is_active ? 'status-active' : 'status-inactive']">
                                        {{ admin.is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <router-link
                                            :to="{ name: 'admin.users.edit', params: { id: admin.id } }"
                                            class="btn-action btn-action-edit"
                                            title="تعديل"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </router-link>
                                        <button
                                            @click="confirmDelete(admin)"
                                            class="btn-action btn-action-delete"
                                            title="حذف"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.total > pagination.per_page" class="pagination-wrapper">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="pagination-btn"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>

                    <span class="pagination-info">
                        صفحة {{ pagination.current_page }} من {{ pagination.last_page }}
                    </span>

                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="pagination-btn"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button @click="showDeleteModal = false" class="btn-close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف مستخدم الإدارة <strong>{{ adminToDelete?.name }}</strong>؟</p>
                    <p class="text-muted">لا يمكن التراجع عن هذا الإجراء.</p>
                </div>
                <div class="modal-footer">
                    <button @click="showDeleteModal = false" class="btn-secondary">إلغاء</button>
                    <button @click="deleteAdmin" class="btn-danger" :disabled="deleting">
                        <i v-if="deleting" class="bi bi-arrow-repeat spinner me-2"></i>
                        {{ deleting ? 'جاري الحذف...' : 'حذف' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const router = useRouter();
const toast = useToast();

const admins = ref([]);
const loading = ref(true);
const search = ref('');
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

const showDeleteModal = ref(false);
const adminToDelete = ref(null);
const deleting = ref(false);

// Fetch admin users
const fetchAdmins = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/admin/admin-users', {
            params: {
                page,
                search: search.value,
            },
        });

        admins.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (error) {
        console.error('Error fetching admin users:', error);
    } finally {
        loading.value = false;
    }
};

// Search with debounce
let searchTimeout;
const searchAdmins = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchAdmins(1);
    }, 500);
};

// Change page
const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchAdmins(page);
    }
};

// Confirm delete
const confirmDelete = (admin) => {
    adminToDelete.value = admin;
    showDeleteModal.value = true;
};

// Delete admin
const deleteAdmin = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/admin/admin-users/${adminToDelete.value.id}`);

        toast.add({
            severity: 'success',
            summary: 'نجاح',
            detail: 'تم حذف مستخدم الإدارة بنجاح',
            life: 3000
        });

        showDeleteModal.value = false;
        adminToDelete.value = null;
        fetchAdmins(pagination.value.current_page);
    } catch (error) {
        console.error('Error deleting admin:', error);
        toast.add({
            severity: 'error',
            summary: 'خطأ',
            detail: error.response?.data?.message || 'فشل في حذف مستخدم الإدارة',
            life: 3000
        });
    } finally {
        deleting.value = false;
    }
};

onMounted(() => {
    fetchAdmins();
});
</script>

<style scoped>

</style>
