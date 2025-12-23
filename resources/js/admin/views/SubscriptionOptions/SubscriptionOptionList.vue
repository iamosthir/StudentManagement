<template>
  <div class="subscription-options-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title gradient-text">خيارات الاشتراك</h1>
        <p class="page-subtitle">إدارة أسعار ومدد الاشتراك</p>
      </div>
      <router-link
        to="/subscription-options/create"
        class="btn btn-gradient"
        v-if="canCreate"
      >
        <i class="bi bi-plus-circle me-2"></i>
        إضافة خيار اشتراك
      </router-link>
    </div>

    <div class="card modern-card">
      <div class="card-body">
        <!-- Filters and Search -->
        <div class="filters-section mb-4">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="search-box">
                <i class="bi bi-search"></i>
                <input
                  type="text"
                  v-model="filters.search"
                  @input="handleSearch"
                  placeholder="البحث في خيارات الاشتراك..."
                  class="form-control"
                />
              </div>
            </div>
            <div class="col-md-3">
              <select v-model="filters.status" @change="loadOptions" class="form-select">
                <option value="">جميع الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
              </select>
            </div>
            <div class="col-md-3">
              <select v-model="filters.payment_type" @change="loadOptions" class="form-select">
                <option value="">جميع أنواع الدفع</option>
                <option value="full">دفع كامل</option>
                <option value="installment">تقسيط</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <TableSkeleton v-if="loading" :rows="5" :columns="6" />

        <!-- Options Table -->
        <div v-else-if="options.length > 0" class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>الاسم</th>
                <th>السعر</th>
                <th>المدة</th>
                <th>نوع الدفع</th>
                <th>البرامج</th>
                <th>الحالة</th>
                <th class="text-center">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="option in options" :key="option.id" class="table-row">
                <td>
                  <div class="fw-bold text-primary">{{ option.name }}</div>
                </td>
                <td>
                  <div class="fw-semibold text-success">${{ parseFloat(option.price).toFixed(2) }}</div>
                </td>
                <td>
                  <div>{{ option.duration_months }} {{ option.duration_months === 1 ? 'شهر' : 'أشهر' }}</div>
                </td>
                <td>
                  <span :class="['badge', option.is_full_payment ? 'bg-primary' : 'bg-warning']">
                    {{ option.is_full_payment ? 'دفع كامل' : 'تقسيط' }}
                  </span>
                </td>
                <td>
                  <div v-if="option.programs && option.programs.length > 0">
                    <span class="badge bg-info me-1" v-for="program in option.programs.slice(0, 2)" :key="program.id">
                      {{ program.name }}
                    </span>
                    <span v-if="option.programs.length > 2" class="text-muted small">
                      +{{ option.programs.length - 2 }} أخرى
                    </span>
                  </div>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span :class="['badge', option.is_active ? 'badge-success' : 'badge-danger']">
                    {{ option.is_active ? 'نشط' : 'غير نشط' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group" role="group">
                    <router-link
                      :to="`/subscription-options/${option.id}/edit`"
                      class="btn btn-sm btn-outline-primary"
                      v-if="canEdit"
                      title="تعديل"
                    >
                      <i class="bi bi-pencil"></i>
                    </router-link>
                    <button
                      @click="confirmDelete(option)"
                      class="btn btn-sm btn-outline-danger"
                      v-if="canDelete"
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

        <!-- Empty State -->
        <div v-else class="empty-state">
          <i class="bi bi-inbox"></i>
          <h5>لم يتم العثور على خيارات اشتراك</h5>
          <p>{{ filters.search ? 'حاول تعديل بحثك' : 'ابدأ بإنشاء أول خيار اشتراك' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="pagination-wrapper">
          <nav>
            <ul class="pagination">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                  <i class="bi bi-chevron-left"></i>
                </a>
              </li>
              <li
                v-for="page in visiblePages"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
                  <i class="bi bi-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">تأكيد الحذف</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>هل أنت متأكد من حذف خيار الاشتراك <strong>{{ optionToDelete?.name }}</strong>؟</p>
            <p class="text-danger small">لا يمكن التراجع عن هذا الإجراء.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" @click="deleteOption" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              حذف
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Modal } from 'bootstrap';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const options = ref([]);
const loading = ref(false);
const deleting = ref(false);
const optionToDelete = ref(null);
let deleteModal = null;
let searchTimeout = null;

const filters = ref({
  search: '',
  status: '',
  payment_type: ''
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
});

// Permissions
const adminUser = ref(null);
onMounted(() => {
  const storedUser = localStorage.getItem('admin_user');
  if (storedUser) {
    adminUser.value = JSON.parse(storedUser);
  }
});

const canCreate = computed(() => adminUser.value?.permissions?.includes('create subscriptions'));
const canEdit = computed(() => adminUser.value?.permissions?.includes('edit subscriptions'));
const canDelete = computed(() => adminUser.value?.permissions?.includes('delete subscriptions'));

const visiblePages = computed(() => {
  const pages = [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;

  let start = Math.max(1, current - 2);
  let end = Math.min(last, current + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

const loadOptions = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      search: filters.value.search,
      status: filters.value.status,
      payment_type: filters.value.payment_type
    };

    const response = await axios.get('/admin/subscription-options', { params });
    options.value = response.data.data;
    pagination.value = {
      current_page: response.data.meta.current_page,
      last_page: response.data.meta.last_page,
      per_page: response.data.meta.per_page,
      total: response.data.meta.total
    };
  } catch (error) {
    console.error('Error loading subscription options:', error);
    alert('Failed to load subscription options');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadOptions(1);
  }, 500);
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadOptions(page);
  }
};

const confirmDelete = (option) => {
  optionToDelete.value = option;
  deleteModal.show();
};

const deleteOption = async () => {
  if (!optionToDelete.value) return;

  deleting.value = true;
  try {
    await axios.delete(`/admin/subscription-options/${optionToDelete.value.id}`);
    deleteModal.hide();
    loadOptions(pagination.value.current_page);
  } catch (error) {
    console.error('Error deleting subscription option:', error);
    if (error.response?.data?.message) {
      alert(error.response.data.message);
    } else {
      alert('Failed to delete subscription option');
    }
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  loadOptions();
  deleteModal = new Modal(document.getElementById('deleteModal'));
});
</script>
