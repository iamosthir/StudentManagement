<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const toast = useToast();

const coupons = ref([]);
const loading = ref(false);
const stats = ref({
  total: 0,
  used: 0,
  unused: 0,
  usage_percentage: 0
});

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const total = ref(0);

// Filters
const statusFilter = ref('');
const searchQuery = ref('');

const adminUser = ref(JSON.parse(localStorage.getItem('admin_user')));
const isAdministrator = computed(() =>
  adminUser.value?.roles?.includes('Administrator') || false
);

const fetchCoupons = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      status: statusFilter.value || undefined,
      search: searchQuery.value || undefined
    };

    const response = await axios.get('/admin/coupons', { params });
    coupons.value = response.data.data;
    currentPage.value = response.data.meta.current_page;
    lastPage.value = response.data.meta.last_page;
    total.value = response.data.meta.total;
  } catch (error) {
    console.error('Error fetching coupons:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch coupons',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const fetchStatistics = async () => {
  try {
    const response = await axios.get('/admin/coupons/statistics');
    stats.value = response.data.data;
  } catch (error) {
    console.error('Error fetching statistics:', error);
  }
};

const handleSearch = () => {
  fetchCoupons(1);
};

const handleFilterChange = () => {
  fetchCoupons(1);
};

const handleDelete = async (coupon) => {
  if (!confirm(`هل أنت متأكد من حذف الكوبون "${coupon.code}"؟`)) {
    return;
  }

  try {
    await axios.delete(`/admin/coupons/${coupon.id}`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Coupon deleted successfully!',
      life: 3000
    });
    fetchCoupons(currentPage.value);
    fetchStatistics();
  } catch (error) {
    console.error('Error deleting coupon:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete coupon',
      life: 3000
    });
  }
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatDateTime = (datetime) => {
  if (!datetime) return '—';
  return new Date(datetime).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  if (!isAdministrator.value) {
    toast.add({
      severity: 'error',
      summary: 'Access Denied',
      detail: 'You do not have permission to access this page.',
      life: 3000
    });
    router.push('/admin/dashboard');
    return;
  }
  fetchCoupons();
  fetchStatistics();
});
</script>

<template>
  <div class="coupon-list-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon">
          <i class="bi bi-ticket-perforated"></i>
        </div>
        <div class="header-text">
          <h1 class="page-title">إدارة الكوبونات</h1>
          <p class="page-subtitle">إدارة كوبونات الخصم وتتبع الاستخدام</p>
        </div>
      </div>
      <button class="btn-add" @click="router.push('/coupons/create')">
        <i class="bi bi-plus-circle"></i>
        <span>إضافة كوبون</span>
      </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card stat-card-primary">
        <div class="stat-icon">
          <i class="bi bi-ticket-perforated-fill"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">إجمالي الكوبونات</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
      </div>

      <div class="stat-card stat-card-success">
        <div class="stat-icon">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">الكوبونات المستخدمة</div>
          <div class="stat-value">{{ stats.used }}</div>
        </div>
      </div>

      <div class="stat-card stat-card-info">
        <div class="stat-icon">
          <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">الكوبونات المتاحة</div>
          <div class="stat-value">{{ stats.unused }}</div>
        </div>
      </div>

      <div class="stat-card stat-card-warning">
        <div class="stat-icon">
          <i class="bi bi-graph-up"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">معدل الاستخدام</div>
          <div class="stat-value">{{ stats.usage_percentage }}%</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
      <div class="filters-row">
        <div class="filter-group">
          <label for="search" class="filter-label">
            <i class="bi bi-search"></i>
            البحث بالكود أو الاسم
          </label>
          <input
            id="search"
            v-model="searchQuery"
            type="text"
            placeholder="أدخل كود الكوبون أو الاسم..."
            class="filter-input"
            @keyup.enter="handleSearch"
          />
        </div>

        <div class="filter-group">
          <label for="status" class="filter-label">
            <i class="bi bi-funnel"></i>
            الحالة
          </label>
          <select
            id="status"
            v-model="statusFilter"
            class="filter-select"
            @change="handleFilterChange"
          >
            <option value="">جميع الكوبونات</option>
            <option value="unused">متاح</option>
            <option value="used">مستخدم</option>
          </select>
        </div>

        <div class="filter-actions">
          <button class="btn-filter" @click="handleSearch">
            <i class="bi bi-search"></i>
            بحث
          </button>
          <button
            class="btn-filter-reset"
            @click="searchQuery = ''; statusFilter = ''; fetchCoupons(1)"
          >
            <i class="bi bi-x-circle"></i>
            إعادة تعيين
          </button>
        </div>
      </div>
    </div>

    <!-- Coupons Table -->
    <div class="table-card">
      <TableSkeleton v-if="loading" :rows="10" :columns="7" />

      <div v-else-if="coupons.length > 0" class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>الكود</th>
              <th>الاسم</th>
              <th>الخصم</th>
              <th>الحالة</th>
              <th>مستخدم من قبل</th>
              <th>تم التطبيق بواسطة</th>
              <th>تاريخ الاستخدام</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="coupon in coupons" :key="coupon.id">
              <td>
                <span class="coupon-code">{{ coupon.code }}</span>
              </td>
              <td>
                <span class="coupon-name">{{ coupon.coupon_name || '—' }}</span>
              </td>
              <td>
                <span class="discount-badge" :class="`discount-${coupon.discount_type}`">
                  <i :class="coupon.discount_type === 'percent' ? 'bi bi-percent' : 'bi bi-currency-dollar'"></i>
                  {{ coupon.discount_value }}{{ coupon.discount_type === 'percent' ? '%' : '' }}
                </span>
              </td>
              <td>
                <span
                  class="status-badge"
                  :class="coupon.is_used ? 'status-danger' : 'status-success'"
                >
                  <i :class="coupon.is_used ? 'bi bi-x-circle-fill' : 'bi bi-check-circle-fill'"></i>
                  {{ coupon.is_used ? 'مستخدم' : 'متاح' }}
                </span>
              </td>
              <td>
                <span v-if="coupon.student">
                  {{ coupon.student.admission_number }} - {{ coupon.student.full_name }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>
                <span v-if="coupon.admin">
                  {{ coupon.admin.name }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>{{ formatDateTime(coupon.used_at) }}</td>
              <td>
                <div class="action-buttons">
                  <button
                    class="btn-icon btn-icon-edit"
                    @click="router.push(`/coupons/${coupon.id}/edit`)"
                    title="تعديل"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    v-if="!coupon.is_used"
                    class="btn-icon btn-icon-delete"
                    @click="handleDelete(coupon)"
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

      <div v-else class="empty-state">
        <i class="bi bi-ticket-perforated"></i>
        <p>لم يتم العثور على كوبونات</p>
        <button class="btn-add" @click="router.push('/coupons/create')">
          <i class="bi bi-plus-circle"></i>
          إنشاء أول كوبون
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="pagination">
        <button
          class="pagination-btn"
          :disabled="currentPage === 1"
          @click="fetchCoupons(currentPage - 1)"
        >
          <i class="bi bi-chevron-left"></i>
          السابق
        </button>

        <span class="pagination-info">
          صفحة {{ currentPage }} من {{ lastPage }} ({{ total }} إجمالي)
        </span>

        <button
          class="pagination-btn"
          :disabled="currentPage === lastPage"
          @click="fetchCoupons(currentPage + 1)"
        >
          التالي
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped src="../../../../css/couponlist.css"></style>
