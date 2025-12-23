<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();
const toast = useToast();

const payments = ref([]);
const loading = ref(false);
const statusFilter = ref('');
const startDate = ref('');
const endDate = ref('');

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
});

const fetchPayments = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      status: statusFilter.value || undefined,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined
    };

    const response = await axios.get('/admin/payments', { params });
    payments.value = response.data.data;
    pagination.value = response.data.meta;
  } catch (error) {
    console.error('Error fetching payments:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch payments',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const markAsPaid = async (payment) => {
  if (!confirm(`Mark payment ${payment.payment_number} as paid?`)) return;

  try {
    await axios.post(`/admin/payments/${payment.id}/mark-paid`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payment marked as paid',
      life: 3000
    });
    fetchPayments(pagination.value.current_page);
  } catch (error) {
    console.error('Error marking payment as paid:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to mark payment as paid',
      life: 3000
    });
  }
};

const markAsCancelled = async (payment) => {
  if (!confirm(`Cancel payment ${payment.payment_number}?`)) return;

  try {
    await axios.post(`/admin/payments/${payment.id}/mark-cancelled`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payment cancelled',
      life: 3000
    });
    fetchPayments(pagination.value.current_page);
  } catch (error) {
    console.error('Error cancelling payment:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to cancel payment',
      life: 3000
    });
  }
};

const getStatusClass = (status) => {
  const classes = {
    paid: 'success',
    pending: 'warning',
    cancelled: 'danger'
  };
  return classes[status] || 'secondary';
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};

const handleFilter = () => {
  fetchPayments(1);
};

const resetFilters = () => {
  statusFilter.value = '';
  startDate.value = '';
  endDate.value = '';
  fetchPayments(1);
};

onMounted(() => {
  fetchPayments();
});
</script>

<template>
  <div class="payments-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title gradient-text">المدفوعات</h1>
          <p class="page-subtitle">إدارة مدفوعات الطلاب والمعاملات</p>
        </div>
        <button class="btn btn-gradient" @click="router.push('/payments/create')">
          <i class="bi bi-plus-circle"></i>
          إضافة دفعة
        </button>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card filter-card">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">الحالة</label>
            <select v-model="statusFilter" class="form-select" @change="handleFilter">
              <option value="">جميع الحالات</option>
              <option value="paid">مدفوع</option>
              <option value="pending">قيد الانتظار</option>
              <option value="cancelled">ملغى</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">تاريخ البدء</label>
            <input
              v-model="startDate"
              type="date"
              class="form-control"
              @change="handleFilter"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">تاريخ الانتهاء</label>
            <input
              v-model="endDate"
              type="date"
              class="form-control"
              @change="handleFilter"
            />
          </div>
          <div class="col-md-3">
            <label class="form-label d-block">&nbsp;</label>
            <button class="btn btn-outline-secondary w-100" @click="resetFilters">
              <i class="bi bi-arrow-clockwise"></i>
              إعادة تعيين
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Payments Table -->
    <div class="card data-card">
      <div class="card-body">
        <TableSkeleton v-if="loading" :rows="10" :columns="7" />

        <div v-else-if="payments.length === 0" class="empty-state">
          <i class="bi bi-credit-card"></i>
          <h3>لم يتم العثور على دفعات</h3>
          <p>ابدأ بتسجيل أول دفعة</p>
          <button class="btn btn-gradient" @click="router.push('/payments/create')">
            إضافة دفعة
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>رقم الدفعة</th>
                <th>الطالب</th>
                <th>المبلغ</th>
                <th>الطريقة</th>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.id">
                <td>
                  <span class="payment-number">{{ payment.payment_number }}</span>
                </td>
                <td>
                  <div v-if="payment.student" class="student-info">
                    <span class="student-name">{{ payment.student.full_name }}</span>
                    <small class="text-muted d-block">{{ payment.student.admission_number }}</small>
                  </div>
                </td>
                <td>
                  <span class="amount-value">{{ formatCurrency(payment.amount) }}</span>
                </td>
                <td>{{ payment.payment_method || '—' }}</td>
                <td>{{ formatDate(payment.paid_at || payment.created_at) }}</td>
                <td>
                  <span class="status-badge" :class="`status-${getStatusClass(payment.status)}`">
                    {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button
                      class="btn btn-sm btn-icon btn-primary"
                      @click="router.push(`/payments/${payment.id}`)"
                      title="عرض التفاصيل"
                    >
                      <i class="bi bi-eye"></i>
                    </button>
                    <button
                      v-if="payment.status === 'pending'"
                      class="btn btn-sm btn-icon btn-success"
                      @click="markAsPaid(payment)"
                      title="وضع علامة مدفوع"
                    >
                      <i class="bi bi-check-circle"></i>
                    </button>
                    <button
                      v-if="payment.status === 'pending'"
                      class="btn btn-sm btn-icon btn-danger"
                      @click="markAsCancelled(payment)"
                      title="إلغاء"
                    >
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination-wrapper">
          <nav>
            <ul class="pagination">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link" @click="fetchPayments(pagination.current_page - 1)">
                  <i class="bi bi-chevron-left"></i>
                </button>
              </li>
              <li
                v-for="page in pagination.last_page"
                :key="page"
                class="page-item"
                :class="{ active: pagination.current_page === page }"
              >
                <button class="page-link" @click="fetchPayments(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link" @click="fetchPayments(pagination.current_page + 1)">
                  <i class="bi bi-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
          <div class="pagination-info">
            عرض {{ payments.length }} من {{ pagination.total }} دفعة
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
