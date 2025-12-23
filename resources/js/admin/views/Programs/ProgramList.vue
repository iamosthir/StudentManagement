<template>
  <div class="programs-container" dir="rtl">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title gradient-text">البرامج</h1>
        <p class="page-subtitle">إدارة البرامج التعليمية</p>
      </div>
      <router-link
        to="/programs/create"
        class="btn btn-gradient"
        v-if="canCreate"
      >
        <i class="bi bi-plus-circle me-2"></i>
        إضافة برنامج
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
                  placeholder="البحث في البرامج..."
                  class="form-control"
                />
              </div>
            </div>
            <div class="col-md-3">
              <select v-model="filters.status" @change="loadPrograms" class="form-select">
                <option value="">جميع الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <TableSkeleton v-if="loading" :rows="5" :columns="5" />

        <!-- Programs Table -->
        <div v-else-if="programs.length > 0" class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>الاسم</th>
                <th>الوصف</th>
                <th>خيارات الاشتراك</th>
                <th>المنتجات</th>
                <th>الحالة</th>
                <th class="text-center">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="program in programs" :key="program.id" class="table-row">
                <td>
                  <div class="fw-bold text-primary">{{ program.name }}</div>
                </td>
                <td>
                  <div class="text-truncate" style="max-width: 300px;" :title="program.description">
                    {{ program.description || '-' }}
                  </div>
                </td>
                <td>
                  <div v-if="program.subscription_options && program.subscription_options.length > 0">
                    <span class="badge bg-info me-1 mb-2" v-for="option in program.subscription_options.slice(0, 2)" :key="option.id">
                      {{ option.name }}
                    </span>
                    <span v-if="program.subscription_options.length > 2" class="text-muted small">
                      +{{ program.subscription_options.length - 2 }} المزيد
                    </span>
                  </div>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <div v-if="program.products && program.products.length > 0">
                    <span class="badge bg-secondary me-1 mb-2" v-for="product in program.products.slice(0, 2)" :key="product.id">
                      {{ product.name }}
                    </span>
                    <span v-if="program.products.length > 2" class="text-muted small">
                      +{{ program.products.length - 2 }} المزيد
                    </span>
                  </div>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <span :class="['badge', program.is_active ? 'badge-success' : 'badge-danger']">
                    {{ program.is_active ? 'نشط' : 'غير نشط' }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group" role="group">
                    <router-link
                      :to="`/programs/${program.id}/edit`"
                      class="btn btn-sm btn-outline-primary"
                      v-if="canEdit"
                      title="تعديل"
                    >
                      <i class="bi bi-pencil"></i>
                    </router-link>
                    <button
                      @click="confirmDelete(program)"
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
          <h5>لم يتم العثور على برامج</h5>
          <p>{{ filters.search ? 'حاول تعديل البحث' : 'ابدأ بإنشاء برنامجك الأول' }}</p>
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
            <p>هل أنت متأكد من حذف البرنامج <strong>{{ programToDelete?.name }}</strong>؟</p>
            <p class="text-danger small">لا يمكن التراجع عن هذا الإجراء.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-danger" @click="deleteProgram" :disabled="deleting">
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
const programs = ref([]);
const loading = ref(false);
const deleting = ref(false);
const programToDelete = ref(null);
let deleteModal = null;
let searchTimeout = null;

const filters = ref({
  search: '',
  status: ''
});

const pagination = ref({
  current_page:1,
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

const canCreate = computed(() => adminUser.value?.permissions?.includes('create programs'));
const canEdit = computed(() => adminUser.value?.permissions?.includes('edit programs'));
const canDelete = computed(() => adminUser.value?.permissions?.includes('delete programs'));

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

const loadPrograms = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      search: filters.value.search,
      status: filters.value.status
    };

    const response = await axios.get('/admin/programs', { params });
    programs.value = response.data.data;
    pagination.value = {
      current_page: response.data.meta.current_page,
      last_page: response.data.meta.last_page,
      per_page: response.data.meta.per_page,
      total: response.data.meta.total
    };
  } catch (error) {
    console.error('Error loading programs:', error);
    alert('فشل في تحميل البرامج');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadPrograms(1);
  }, 500);
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadPrograms(page);
  }
};

const confirmDelete = (program) => {
  programToDelete.value = program;
  deleteModal.show();
};

const deleteProgram = async () => {
  if (!programToDelete.value) return;

  deleting.value = true;
  try {
    await axios.delete(`/admin/programs/${programToDelete.value.id}`);
    deleteModal.hide();
    loadPrograms(pagination.value.current_page);
  } catch (error) {
    console.error('Error deleting program:', error);
    if (error.response?.data?.message) {
      alert(error.response.data.message);
    } else {
      alert('فشل في حذف البرنامج');
    }
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  loadPrograms();
  deleteModal = new Modal(document.getElementById('deleteModal'));
});
</script>
