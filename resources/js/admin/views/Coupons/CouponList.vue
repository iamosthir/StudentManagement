<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

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
    alert('Failed to fetch coupons');
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
  if (!confirm(`Are you sure you want to delete coupon "${coupon.code}"?`)) {
    return;
  }

  try {
    await axios.delete(`/admin/coupons/${coupon.id}`);
    alert('Coupon deleted successfully!');
    fetchCoupons(currentPage.value);
    fetchStatistics();
  } catch (error) {
    console.error('Error deleting coupon:', error);
    alert(error.response?.data?.message || 'Failed to delete coupon');
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
    alert('You do not have permission to access this page.');
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
          <h1 class="page-title">Coupon Management</h1>
          <p class="page-subtitle">Manage discount coupons and track usage</p>
        </div>
      </div>
      <button class="btn-add" @click="router.push('/coupons/create')">
        <i class="bi bi-plus-circle"></i>
        <span>Create Coupon</span>
      </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card stat-card-primary">
        <div class="stat-icon">
          <i class="bi bi-ticket-perforated-fill"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Coupons</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
      </div>

      <div class="stat-card stat-card-success">
        <div class="stat-icon">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Used Coupons</div>
          <div class="stat-value">{{ stats.used }}</div>
        </div>
      </div>

      <div class="stat-card stat-card-info">
        <div class="stat-icon">
          <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Available Coupons</div>
          <div class="stat-value">{{ stats.unused }}</div>
        </div>
      </div>

      <div class="stat-card stat-card-warning">
        <div class="stat-icon">
          <i class="bi bi-graph-up"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Usage Rate</div>
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
            Search by Code or Name
          </label>
          <input
            id="search"
            v-model="searchQuery"
            type="text"
            placeholder="Enter coupon code or name..."
            class="filter-input"
            @keyup.enter="handleSearch"
          />
        </div>

        <div class="filter-group">
          <label for="status" class="filter-label">
            <i class="bi bi-funnel"></i>
            Status
          </label>
          <select
            id="status"
            v-model="statusFilter"
            class="filter-select"
            @change="handleFilterChange"
          >
            <option value="">All Coupons</option>
            <option value="unused">Available</option>
            <option value="used">Used</option>
          </select>
        </div>

        <div class="filter-actions">
          <button class="btn-filter" @click="handleSearch">
            <i class="bi bi-search"></i>
            Search
          </button>
          <button
            class="btn-filter-reset"
            @click="searchQuery = ''; statusFilter = ''; fetchCoupons(1)"
          >
            <i class="bi bi-x-circle"></i>
            Reset
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
              <th>Code</th>
              <th>Name</th>
              <th>Discount</th>
              <th>Status</th>
              <th>Used By</th>
              <th>Applied By</th>
              <th>Used At</th>
              <th>Actions</th>
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
                  {{ coupon.is_used ? 'Used' : 'Available' }}
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
                    title="Edit"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    v-if="!coupon.is_used"
                    class="btn-icon btn-icon-delete"
                    @click="handleDelete(coupon)"
                    title="Delete"
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
        <p>No coupons found</p>
        <button class="btn-add" @click="router.push('/coupons/create')">
          <i class="bi bi-plus-circle"></i>
          Create Your First Coupon
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
          Previous
        </button>

        <span class="pagination-info">
          Page {{ currentPage }} of {{ lastPage }} ({{ total }} total)
        </span>

        <button
          class="pagination-btn"
          :disabled="currentPage === lastPage"
          @click="fetchCoupons(currentPage + 1)"
        >
          Next
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.coupon-list-page {
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
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
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
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.btn-add {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.stat-card-primary .stat-icon {
  background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
}

.stat-card-success .stat-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-card-info .stat-icon {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.stat-card-warning .stat-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
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
  grid-template-columns: 1fr 1fr auto;
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
  color: #8b5cf6;
}

.filter-input,
.filter-select {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.btn-filter:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
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

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
}

.status-success {
  background: #d1fae5;
  color: #065f46;
}

.status-danger {
  background: #fee2e2;
  color: #991b1b;
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

.btn-icon-edit {
  background: #dbeafe;
  color: #1e40af;
}

.btn-icon-edit:hover {
  background: #3b82f6;
  color: white;
  transform: scale(1.1);
}

.btn-icon-delete {
  background: #fee2e2;
  color: #991b1b;
}

.btn-icon-delete:hover {
  background: #ef4444;
  color: white;
  transform: scale(1.1);
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
  border-color: #8b5cf6;
  color: #8b5cf6;
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
  .coupon-list-page {
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
