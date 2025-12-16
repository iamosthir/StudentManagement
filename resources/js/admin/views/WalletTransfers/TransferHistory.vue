<template>
  <div class="transfer-history-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="bi bi-clock-history gradient-icon"></i>
          Transfer History
        </h1>
        <p class="page-subtitle">View all wallet transfer requests and their status</p>
      </div>
      <div class="header-actions">
        <Button
          v-if="isAdministrator"
          label="Transaction Logs"
          icon="bi bi-file-text"
          @click="router.push('/transfers/transaction-logs')"
          severity="info"
          outlined
        />
        <Button
          v-if="isAdministrator"
          label="Pending Transfers"
          icon="bi bi-hourglass-split"
          @click="router.push('/transfers/pending')"
          severity="warning"
          outlined
          :badge="pendingCount > 0 ? pendingCount.toString() : null"
        />
        <Button
          label="New Transfer"
          icon="bi bi-plus-lg"
          @click="router.push('/transfers/create')"
        />
      </div>
    </div>

    <!-- Loading State -->
    <TableSkeleton v-if="loading" :rows="10" :columns="8" />

    <!-- No Transfers -->
    <div v-else-if="transfers.length === 0" class="empty-state">
      <i class="bi bi-inbox-fill"></i>
      <h3>No Transfer History</h3>
      <p>No wallet transfers have been made yet.</p>
      <Button
        label="Create First Transfer"
        icon="bi bi-plus-lg"
        @click="router.push('/admin/wallet-transfers/create')"
      />
    </div>

    <!-- Transfers Table -->
    <div v-else class="transfers-table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>From</th>
            <th>To</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Processed By</th>
            <th>Date</th>
            <th v-if="isAdministrator">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="transfer in transfers" :key="transfer.id">
            <td>
              <span class="badge badge-primary">#{{ transfer.id }}</span>
            </td>
            <td>
              <div class="admin-info">
                <div class="admin-name">{{ transfer.from_admin.name }}</div>
                <div class="admin-email">{{ transfer.from_admin.email }}</div>
              </div>
            </td>
            <td>
              <div class="admin-info">
                <div class="admin-name">{{ transfer.to_admin.name }}</div>
                <div class="admin-email">{{ transfer.to_admin.email }}</div>
              </div>
            </td>
            <td>
              <span class="amount">${{ parseFloat(transfer.amount).toFixed(2) }}</span>
            </td>
            <td>
              <span :class="['status-badge', `status-${transfer.status}`]">
                <i :class="getStatusIcon(transfer.status)"></i>
                {{ getStatusLabel(transfer.status) }}
              </span>
              <div v-if="transfer.cancellation_reason" class="cancellation-reason">
                {{ transfer.cancellation_reason }}
              </div>
            </td>
            <td>
              <span class="notes">{{ transfer.notes || '-' }}</span>
            </td>
            <td>
              <div v-if="transfer.processed_by" class="admin-info">
                <div class="admin-name">{{ transfer.processed_by.name }}</div>
                <div class="admin-email">{{ transfer.processed_by.email }}</div>
              </div>
              <span v-else class="text-muted">-</span>
            </td>
            <td>
              <div class="date-info">
                <div class="date">{{ formatDate(transfer.created_at) }}</div>
                <div v-if="transfer.processed_at" class="processed-date">
                  Processed: {{ formatDate(transfer.processed_at) }}
                </div>
              </div>
            </td>
            <td v-if="isAdministrator">
              <div v-if="transfer.status === 'pending'" class="action-buttons">
                <Button
                  label="Accept"
                  icon="bi bi-check-lg"
                  severity="success"
                  size="small"
                  @click="processTransfer(transfer.id, 'accept')"
                  :loading="processing[transfer.id]"
                />
                <Button
                  label="Reject"
                  icon="bi bi-x-lg"
                  severity="danger"
                  size="small"
                  outlined
                  @click="showRejectDialog(transfer.id)"
                  :loading="processing[transfer.id]"
                />
              </div>
              <span v-else class="text-muted">-</span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
        <Button
          label="Previous"
          icon="bi bi-chevron-left"
          :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)"
          outlined
        />
        <span class="page-info">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>
        <Button
          label="Next"
          icon="bi bi-chevron-right"
          iconPos="right"
          :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
          outlined
        />
      </div>
    </div>

    <!-- Reject Dialog -->
    <Dialog
      v-model:visible="rejectDialogVisible"
      modal
      header="Reject Transfer"
      :style="{ width: '500px' }"
    >
      <div class="reject-dialog-content">
        <p>Please provide a reason for rejecting this transfer:</p>
        <Textarea
          v-model="rejectionReason"
          rows="4"
          placeholder="Enter rejection reason..."
          :class="{ 'p-invalid': rejectionError }"
        />
        <small v-if="rejectionError" class="error-message">{{ rejectionError }}</small>
      </div>
      <template #footer>
        <Button
          label="Cancel"
          severity="secondary"
          outlined
          @click="rejectDialogVisible = false"
        />
        <Button
          label="Reject Transfer"
          severity="danger"
          @click="confirmReject"
          :loading="processing[selectedTransferId]"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

const transfers = ref([]);
const loading = ref(false);
const pendingCount = ref(0);
const processing = ref({});
const rejectDialogVisible = ref(false);
const selectedTransferId = ref(null);
const rejectionReason = ref('');
const rejectionError = ref('');
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
});

const adminUser = computed(() => {
  const user = localStorage.getItem('admin_user');
  return user ? JSON.parse(user) : null;
});

const isAdministrator = computed(() => {
  return adminUser.value?.roles?.includes('Administrator') ?? false;
});

onMounted(() => {
  fetchTransfers();
  if (isAdministrator.value) {
    fetchPendingCount();
  }
});

const fetchTransfers = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get(`/admin/wallet-transfers?page=${page}`);
    transfers.value = response.data.transfers;
    pagination.value = response.data.pagination;
  } catch (error) {
    console.error('Error fetching transfers:', error);
    alert('Failed to load transfer history.');
  } finally {
    loading.value = false;
  }
};

const fetchPendingCount = async () => {
  try {
    const response = await axios.get('/admin/wallet-transfers/pending');
    pendingCount.value = response.data.transfers.length;
  } catch (error) {
    console.error('Error fetching pending count:', error);
  }
};

const changePage = (page) => {
  fetchTransfers(page);
};

const getStatusIcon = (status) => {
  const icons = {
    pending: 'bi bi-hourglass-split',
    accepted: 'bi bi-check-circle-fill',
    rejected: 'bi bi-x-circle-fill',
    cancelled: 'bi bi-exclamation-circle-fill'
  };
  return icons[status] || 'bi bi-question-circle-fill';
};

const getStatusLabel = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1);
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

const processTransfer = async (transferId, action) => {
  if (action === 'accept') {
    if (!confirm('Are you sure you want to accept this transfer?')) {
      return;
    }
  }

  processing.value[transferId] = true;

  try {
    const payload = { action };
    if (action === 'reject') {
      payload.rejection_reason = rejectionReason.value;
    }

    const response = await axios.post(`/admin/wallet-transfers/${transferId}/process`, payload);

    alert(response.data.message);

    // Refresh the transfer list
    await fetchTransfers(pagination.value.current_page);

    if (action === 'reject') {
      rejectDialogVisible.value = false;
      rejectionReason.value = '';
    }
  } catch (error) {
    if (error.response?.status === 422) {
      rejectionError.value = error.response.data.errors?.rejection_reason?.[0] || '';
    } else {
      alert(error.response?.data?.message || 'Failed to process transfer.');
    }
  } finally {
    processing.value[transferId] = false;
  }
};

const showRejectDialog = (transferId) => {
  selectedTransferId.value = transferId;
  rejectionReason.value = '';
  rejectionError.value = '';
  rejectDialogVisible.value = true;
};

const confirmReject = () => {
  if (!rejectionReason.value.trim()) {
    rejectionError.value = 'Rejection reason is required.';
    return;
  }
  processTransfer(selectedTransferId.value, 'reject');
};
</script>

<style scoped>
.transfer-history-container {
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
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: rotate 3s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.page-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
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
  margin: 0 0 1.5rem 0;
}

.transfers-table-container {
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

.amount {
  font-weight: 700;
  color: #10b981;
  font-size: 1.125rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-accepted {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.status-cancelled {
  background: #f3f4f6;
  color: #4b5563;
}

.cancellation-reason {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.25rem;
  font-style: italic;
}

.notes {
  color: #64748b;
  font-size: 0.875rem;
  max-width: 200px;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.date-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date {
  color: #1e293b;
  font-size: 0.875rem;
}

.processed-date {
  color: #64748b;
  font-size: 0.75rem;
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

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.reject-dialog-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.reject-dialog-content p {
  margin: 0;
  color: #475569;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .transfer-history-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .transfers-table-container {
    overflow-x: auto;
  }
}
</style>
