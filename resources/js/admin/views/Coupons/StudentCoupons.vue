<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const toast = useToast();

const studentsWithCoupons = ref([]);
const loading = ref(false);

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const total = ref(0);

// Filters
const searchQuery = ref('');

const adminUser = ref(JSON.parse(localStorage.getItem('admin_user')));
const isAdministrator = computed(() =>
  adminUser.value?.roles?.includes('Administrator') || false
);

const fetchStudentsWithCoupons = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined
    };

    const response = await axios.get('/admin/coupons/students-with-coupons', { params });
    studentsWithCoupons.value = response.data.data;
    currentPage.value = response.data.meta.current_page;
    lastPage.value = response.data.meta.last_page;
    total.value = response.data.meta.total;
  } catch (error) {
    console.error('Error fetching students with coupons:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch students with coupons',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};


const handleSearch = () => {
  fetchStudentsWithCoupons(1);
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

const viewStudentDetails = (studentId) => {
  router.push(`/students/${studentId}`);
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
  fetchStudentsWithCoupons();
});
</script>

<template>
  <div class="student-coupons-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon">
          <i class="bi bi-people-fill"></i>
        </div>
        <div class="header-text">
          <h1 class="page-title">Students with Coupons</h1>
          <p class="page-subtitle">View students who have applied coupons and their discount details</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
      <div class="filters-row">
        <div class="filter-group">
          <label for="search" class="filter-label">
            <i class="bi bi-search"></i>
            Search by Student Name, Admission Number, or Coupon Code
          </label>
          <input
            id="search"
            v-model="searchQuery"
            type="text"
            placeholder="Enter search term..."
            class="filter-input"
            @keyup.enter="handleSearch"
          />
        </div>

        <div class="filter-actions">
          <button class="btn-filter" @click="handleSearch">
            <i class="bi bi-search"></i>
            Search
          </button>
          <button
            class="btn-filter-reset"
            @click="searchQuery = ''; fetchStudentsWithCoupons(1)"
          >
            <i class="bi bi-x-circle"></i>
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Students with Coupons Table -->
    <div class="table-card">
      <TableSkeleton v-if="loading" :rows="10" :columns="6" />

      <div v-else-if="studentsWithCoupons.length > 0" class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Coupon Code</th>
              <th>Coupon Name</th>
              <th>Discount</th>
              <th>Applied By</th>
              <th>Used At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in studentsWithCoupons" :key="record.id">
              <td>
                <div class="student-info">
                  <span class="student-name">{{ record.student?.full_name || 'Unknown' }}</span>
                  <span class="student-admission">{{ record.student?.admission_number || 'N/A' }}</span>
                </div>
              </td>
              <td>
                <span class="coupon-code">{{ record.coupon_code }}</span>
              </td>
              <td>
                <span class="coupon-name">{{ record.coupon_name || '—' }}</span>
              </td>
              <td>
                <span class="discount-badge" :class="`discount-${record.discount_type}`">
                  <i :class="record.discount_type === 'percent' ? 'bi bi-percent' : 'bi bi-currency-dollar'"></i>
                  {{ record.discount_amount_display }}
                </span>
              </td>
              <td>
                <span v-if="record.admin" class="admin-name">
                  {{ record.admin.name }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>{{ formatDateTime(record.used_at) }}</td>
              <td>
                <div class="action-buttons">
                  <button
                    class="btn-icon btn-icon-view"
                    @click="viewStudentDetails(record.student?.id)"
                    title="View Student Details"
                    :disabled="!record.student?.id"
                  >
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="empty-state">
        <i class="bi bi-people"></i>
        <p>No students found with applied coupons</p>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="pagination">
        <button
          class="pagination-btn"
          :disabled="currentPage === 1"
          @click="fetchStudentsWithCoupons(currentPage - 1)"
        >
          <i class="bi bi-chevron-left"></i>
          Previous
        </button>

        <span class="pagination-info">
          Page {{ currentPage }} of {{ lastPage }} ({{ total }} total)
        </span>

        <button
          class="pagination-btn"
          :disabled="currentPage === lastPage"
          @click="fetchStudentsWithCoupons(currentPage + 1)"
        >
          Next
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped src="../../../../css/studentcoupon.css"></style>
