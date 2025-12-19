<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

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
    alert('Failed to fetch students with coupons');
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
    alert('You do not have permission to access this page.');
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

<style scoped>
.student-coupons-page {
  padding: 2rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.header-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.filters-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.filters-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #334155;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.filter-label i {
  color: #10b981;
}

.filter-input {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  min-width: 300px;
}

.filter-input:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-filter,
.btn-filter-reset {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-filter {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-filter:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-filter-reset {
  background: white;
  border: 2px solid #e2e8f0;
  color: #64748b;
}

.btn-filter-reset:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.table-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.data-table thead th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  text-align: left;
  padding: 1rem;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.data-table tbody td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.student-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.student-name {
  font-weight: 600;
  color: #1e293b;
}

.student-admission {
  font-size: 0.875rem;
  color: #64748b;
}

.coupon-code {
  font-family: 'Courier New', monospace;
  font-weight: 700;
  font-size: 1.1rem;
  color: #6366f1;
  background: #f0f0ff;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
}

.coupon-name {
  font-weight: 500;
  color: #334155;
  max-width: 200px;
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.discount-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
}

.discount-percent {
  background: #dbeafe;
  color: #1e40af;
}

.discount-fixed {
  background: #d1fae5;
  color: #065f46;
}

.admin-name {
  font-weight: 500;
  color: #334155;
}

.text-muted {
  color: #94a3b8;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.btn-icon-view {
  background: #dbeafe;
  color: #1e40af;
}

.btn-icon-view:hover:not(:disabled) {
  background: #3b82f6;
  color: white;
  transform: scale(1.1);
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #94a3b8;
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid #f1f5f9;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  color: #64748b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pagination-btn:not(:disabled):hover {
  border-color: #10b981;
  color: #10b981;
  transform: translateY(-2px);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: #64748b;
  font-weight: 500;
}

@media (max-width: 768px) {
  .student-coupons-page {
    padding: 1rem;
  }

  .filters-row {
    grid-template-columns: 1fr;
  }

  .filter-actions {
    width: 100%;
  }

  .filter-actions button {
    flex: 1;
  }

  .pagination {
    flex-direction: column;
    gap: 1rem;
  }

  .pagination-info {
    order: -1;
  }
}
</style>
