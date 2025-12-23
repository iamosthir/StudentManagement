<template>
  <div class="transaction-logs-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="bi bi-file-text gradient-icon"></i>
          سجلات المعاملات
        </h1>
        <p class="page-subtitle">عرض جميع سجلات المعاملات للتدقيق والتتبع</p>
      </div>
      <Button
        label="العودة إلى التحويلات"
        icon="bi bi-arrow-left"
        @click="router.push('/transfers')"
        severity="secondary"
        outlined
      />
    </div>

    <!-- Loading State -->
    <TableSkeleton v-if="loading" :rows="15" :columns="7" />

    <!-- No Logs -->
    <div v-else-if="logs.length === 0" class="empty-state">
      <i class="bi bi-inbox-fill"></i>
      <h3>لا توجد سجلات معاملات</h3>
      <p>لم يتم تسجيل أي سجلات معاملات بعد.</p>
    </div>

    <!-- Logs Table -->
    <div v-else class="logs-table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المسؤول</th>
            <th>النوع</th>
            <th>المبلغ</th>
            <th>الرصيد قبل</th>
            <th>الرصيد بعد</th>
            <th>الوصف</th>
            <th>الدفعة</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>
              <span class="badge badge-primary">#{{ log.id }}</span>
            </td>
            <td>
              <div v-if="log.admin" class="admin-info">
                <div class="admin-name">{{ log.admin.name }}</div>
                <div class="admin-email">{{ log.admin.email }}</div>
              </div>
              <span v-else class="text-muted">-</span>
            </td>
            <td>
              <span :class="['type-badge', `type-${log.transaction_type}`]">
                {{ formatType(log.transaction_type) }}
              </span>
            </td>
            <td>
              <span :class="['amount', getAmountClass(log.transaction_type)]">
                ${{ parseFloat(log.amount).toFixed(2) }}
              </span>
            </td>
            <td>
              <span class="balance">${{ parseFloat(log.balance_before).toFixed(2) }}</span>
            </td>
            <td>
              <span class="balance">${{ parseFloat(log.balance_after).toFixed(2) }}</span>
            </td>
            <td>
              <span class="description">{{ log.description || '-' }}</span>
            </td>
            <td>
              <span v-if="log.payment_id" class="payment-link">
                #{{ log.payment_id }}
              </span>
              <span v-else class="text-muted">-</span>
            </td>
            <td>
              <span class="date">{{ formatDate(log.created_at) }}</span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
        <Button
          label="السابق"
          icon="bi bi-chevron-left"
          :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)"
          outlined
        />
        <span class="page-info">
          صفحة {{ pagination.current_page }} من {{ pagination.last_page }}
          ({{ pagination.total }} إجمالي)
        </span>
        <Button
          label="التالي"
          icon="bi bi-chevron-right"
          iconPos="right"
          :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
          outlined
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

const logs = ref([]);
const loading = ref(false);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 50,
  total: 0
});

onMounted(() => {
  fetchLogs();
});

const fetchLogs = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get(`/admin/wallet-transfers/transaction-logs?page=${page}`);
    logs.value = response.data.logs;
    pagination.value = response.data.pagination;
  } catch (error) {
    console.error('Error fetching transaction logs:', error);
    alert('Failed to load transaction logs.');
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  fetchLogs(page);
};

const formatType = (type) => {
  return type.charAt(0).toUpperCase() + type.slice(1);
};

const getAmountClass = (type) => {
  if (type === 'refund' || type === 'adjustment') {
    return 'amount-negative';
  }
  return 'amount-positive';
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
.transaction-logs-container {
  padding: 2rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header-content {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.gradient-icon {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.page-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.empty-state {
  background: white;
  border-radius: 16px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.empty-state i {
  font-size: 4rem;
  color: #94a3b8;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.empty-state p {
  color: #64748b;
  margin: 0;
}

.logs-table-container {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.data-table tbody tr {
  transition: background-color 0.2s;
}

.data-table tbody tr:hover {
  background-color: #f8fafc;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.badge-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
}

.admin-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.admin-name {
  font-weight: 600;
  color: #1e293b;
}

.admin-email {
  font-size: 0.875rem;
  color: #64748b;
}

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  white-space: nowrap;
}

.type-payment {
  background: #d1fae5;
  color: #065f46;
}

.type-refund {
  background: #fee2e2;
  color: #991b1b;
}

.type-adjustment {
  background: #fef3c7;
  color: #92400e;
}

.amount {
  font-weight: 700;
  font-size: 1.125rem;
}

.amount-positive {
  color: #10b981;
}

.amount-negative {
  color: #ef4444;
}

.balance {
  font-weight: 600;
  color: #64748b;
}

.description {
  color: #64748b;
  font-size: 0.875rem;
  max-width: 250px;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.payment-link {
  color: #6366f1;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

.payment-link:hover {
  color: #8b5cf6;
}

.date {
  color: #64748b;
  font-size: 0.875rem;
  white-space: nowrap;
}

.text-muted {
  color: #94a3b8;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.page-info {
  color: #64748b;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .transaction-logs-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .logs-table-container {
    overflow-x: auto;
  }
}
</style>
