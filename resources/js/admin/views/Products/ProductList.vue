<template>
  <div class="products-page" dir="rtl">
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">المنتجات</h1>
          <p class="page-subtitle">إدارة جميع المنتجات</p>
        </div>
        <router-link
          to="/products/create"
          class="btn btn-primary"
        >
          <i class="bi bi-plus-circle me-2"></i>
          إضافة منتج
        </router-link>
      </div>
    </div>

    <div class="filters-card">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="search-box">
            <i class="bi bi-search search-icon"></i>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="البحث في المنتجات..."
              @input="debouncedSearch"
            />
          </div>
        </div>
        <div class="col-md-3">
          <select v-model="filters.status" class="form-select" @change="loadProducts">
            <option value="">جميع الحالات</option>
            <option value="active">نشط</option>
            <option value="inactive">غير نشط</option>
          </select>
        </div>
        <div class="col-md-3">
          <select v-model="filters.type" class="form-select" @change="loadProducts">
            <option value="">جميع الأنواع</option>
            <option v-for="type in productTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary w-100" @click="resetFilters">
            <i class="bi bi-arrow-clockwise me-1"></i>
            إعادة تعيين
          </button>
        </div>
      </div>
    </div>

    <div class="products-card">
      <TableSkeleton v-if="loading" :rows="5" :columns="5" />

      <div v-else-if="products.length === 0" class="empty-state">
        <i class="bi bi-box-seam"></i>
        <h3>لم يتم العثور على منتجات</h3>
        <p>{{ filters.search ? 'حاول تعديل فلاتر البحث' : 'ابدأ بإنشاء منتجك الأول' }}</p>
        <router-link
          v-if="!filters.search"
          to="/products/create"
          class="btn btn-primary mt-3"
        >
          <i class="bi bi-plus-circle me-2"></i>
          إضافة منتج
        </router-link>
      </div>

      <div v-else class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>الاسم</th>
              <th>السعر</th>
              <th>النوع</th>
              <th>البرامج</th>
              <th>الحالة</th>
              <th class="text-end">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td>
                <div class="product-name">{{ product.name }}</div>
              </td>
              <td>
                <span class="price-badge">${{ parseFloat(product.price).toFixed(2) }}</span>
              </td>
              <td>
                <span class="type-badge">{{ product.type_label }}</span>
              </td>
              <td>
                <div v-if="product.programs && product.programs.length > 0" class="programs-list">
                  <span
                    v-for="program in product.programs.slice(0, 2)"
                    :key="program.id"
                    class="program-tag"
                  >
                    {{ program.name }}
                  </span>
                  <span v-if="product.programs.length > 2" class="program-tag more">
                    +{{ product.programs.length - 2 }} المزيد
                  </span>
                </div>
                <span v-else class="text-muted">لا توجد برامج</span>
              </td>
              <td>
                <span :class="['status-badge', product.is_active ? 'status-active' : 'status-inactive']">
                  {{ product.is_active ? 'نشط' : 'غير نشط' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <router-link
                    :to="`/products/${product.id}/edit`"
                    class="btn-action btn-action-edit"
                    title="تعديل"
                  >
                    <i class="bi bi-pencil"></i>
                  </router-link>
                  <button
                    @click="confirmDelete(product)"
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

      <div v-if="pagination.total > 0" class="pagination-wrapper">
        <div class="pagination-info">
          عرض {{ pagination.from }} إلى {{ pagination.to }} من {{ pagination.total }} منتج
        </div>
        <nav class="pagination-nav">
          <button
            class="btn btn-sm btn-outline-primary"
            :disabled="pagination.current_page === 1"
            @click="changePage(pagination.current_page - 1)"
          >
            <i class="bi bi-chevron-left"></i>
            السابق
          </button>
          <span class="pagination-current">
            الصفحة {{ pagination.current_page }} من {{ pagination.last_page }}
          </span>
          <button
            class="btn btn-sm btn-outline-primary"
            :disabled="pagination.current_page === pagination.last_page"
            @click="changePage(pagination.current_page + 1)"
          >
            التالي
            <i class="bi bi-chevron-right"></i>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const toast = useToast();

const loading = ref(false);
const products = ref([]);
const productTypes = ref([]);
const filters = ref({
  search: '',
  status: '',
  type: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

let debounceTimer = null;

const debouncedSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    loadProducts();
  }, 500);
};

const loadProducts = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      search: filters.value.search || undefined,
      status: filters.value.status || undefined,
      type: filters.value.type || undefined,
    };

    const response = await axios.get('/admin/products', { params });
    products.value = response.data.data;

    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
      from: response.data.from,
      to: response.data.to,
    };
  } catch (error) {
    console.error('Error loading products:', error);
  } finally {
    loading.value = false;
  }
};

const loadProductTypes = async () => {
  try {
    const response = await axios.get('/admin/products/types');
    productTypes.value = response.data.data;
  } catch (error) {
    console.error('Error loading product types:', error);
  }
};

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    type: '',
  };
  loadProducts();
};

const changePage = (page) => {
  loadProducts(page);
};

const confirmDelete = async (product) => {
  if (!confirm(`هل أنت متأكد من حذف "${product.name}"؟`)) {
    return;
  }

  try {
    await axios.delete(`/admin/products/${product.id}`);
    toast.add({
      severity: 'success',
      summary: 'نجاح',
      detail: 'تم حذف المنتج بنجاح',
      life: 3000
    });
    loadProducts(pagination.value.current_page);
  } catch (error) {
    if (error.response?.data?.message) {
      toast.add({
        severity: 'error',
        summary: 'خطأ',
        detail: error.response.data.message,
        life: 3000
      });
    } else {
      toast.add({
        severity: 'error',
        summary: 'خطأ',
        detail: 'خطأ في حذف المنتج',
        life: 3000
      });
    }
  }
};

onMounted(() => {
  loadProducts();
  loadProductTypes();
});
</script>

<style scoped>
.products-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  animation: fadeIn 0.6s ease-out;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
}

.page-subtitle {
  color: #64748b;
  margin: 0.25rem 0 0 0;
  font-size: 0.95rem;
}

.filters-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.08);
  animation: fadeIn 0.6s ease-out 0.1s backwards;
}

.search-box {
  position: relative;
}

.search-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-box .form-control {
  padding-right: 2.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.search-box .form-control:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-select {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.form-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.products-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.08);
  animation: fadeIn 0.6s ease-out 0.2s backwards;
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.data-table thead th {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  padding: 1rem;
  text-align: right;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: none;
}

.data-table tbody tr {
  transition: all 0.3s ease;
  border-bottom: 1px solid #f1f5f9;
}

.data-table tbody tr:hover {
  background: linear-gradient(90deg, rgba(99, 102, 241, 0.03) 0%, rgba(139, 92, 246, 0.03) 100%);
  transform: translateX(2px);
}

.data-table tbody td {
  padding: 1rem;
  color: #1e293b;
}

.product-name {
  font-weight: 600;
  color: #1e293b;
}

.price-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
}

.type-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
}

.programs-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.program-tag {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #1e293b;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.program-tag.more {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.status-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.status-inactive {
  background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
  color: white;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-start;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.btn-action-edit {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-action-edit:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.btn-action-delete {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.btn-action-delete:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-state i {
  font-size: 4rem;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1rem;
  animation: float 3s ease-in-out infinite;
}

.empty-state h3 {
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #64748b;
}

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  color: #64748b;
  font-size: 0.875rem;
}

.pagination-nav {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.pagination-current {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

@media (max-width: 768px) {
  .products-page {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .table-responsive {
    overflow-x: auto;
  }

  .data-table {
    min-width: 800px;
  }
}
</style>
