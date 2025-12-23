<template>
  <div class="pending-transfers-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="bi bi-hourglass-split gradient-icon"></i>
          التحويلات المعلقة
        </h1>
        <p class="page-subtitle">مراجعة والموافقة على تحويلات المحفظة المعلقة</p>
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
    <TableSkeleton v-if="loading" :rows="5" :columns="6" />

    <!-- No Pending Transfers -->
    <div v-else-if="transfers.length === 0" class="empty-state">
      <i class="bi bi-check-circle-fill"></i>
      <h3>لا توجد تحويلات معلقة</h3>
      <p>تمت معالجة جميع طلبات التحويل.</p>
    </div>

    <!-- Pending Transfers Table -->
    <div v-else class="transfers-table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>من</th>
            <th>إلى</th>
            <th>المبلغ</th>
            <th>ملاحظات</th>
            <th>طلب في</th>
            <th>الإجراءات</th>
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
              <span class="notes">{{ transfer.notes || '-' }}</span>
            </td>
            <td>
              <span class="date">{{ formatDate(transfer.created_at) }}</span>
            </td>
            <td>
              <div class="action-buttons">
                <Button
                  label="قبول"
                  icon="bi bi-check-lg"
                  severity="success"
                  size="small"
                  @click="processTransfer(transfer.id, 'accept')"
                  :loading="processing[transfer.id]"
                />
                <Button
                  label="رفض"
                  icon="bi bi-x-lg"
                  severity="danger"
                  size="small"
                  outlined
                  @click="showRejectDialog(transfer.id)"
                  :loading="processing[transfer.id]"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reject Dialog -->
    <Dialog
      v-model:visible="rejectDialogVisible"
      modal
      header="رفض التحويل"
      :style="{ width: '500px' }"
    >
      <div class="reject-dialog-content">
        <p>يرجى تقديم سبب لرفض هذا التحويل:</p>
        <Textarea
          v-model="rejectionReason"
          rows="4"
          placeholder="أدخل سبب الرفض..."
          :class="{ 'p-invalid': rejectionError }"
        />
        <small v-if="rejectionError" class="error-message">{{ rejectionError }}</small>
      </div>
      <template #footer>
        <Button
          label="إلغاء"
          severity="secondary"
          outlined
          @click="rejectDialogVisible = false"
        />
        <Button
          label="رفض التحويل"
          severity="danger"
          @click="confirmReject"
          :loading="processing[selectedTransferId]"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import TableSkeleton from '@/components/TableSkeleton.vue';

const router = useRouter();

const transfers = ref([]);
const loading = ref(false);
const processing = ref({});
const rejectDialogVisible = ref(false);
const selectedTransferId = ref(null);
const rejectionReason = ref('');
const rejectionError = ref('');

onMounted(() => {
  fetchPendingTransfers();
});

const fetchPendingTransfers = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/admin/wallet-transfers/pending');
    transfers.value = response.data.transfers;
  } catch (error) {
    console.error('Error fetching pending transfers:', error);
    alert('Failed to load pending transfers.');
  } finally {
    loading.value = false;
  }
};

const processTransfer = async (transferId, action) => {
  if (action === 'accept') {
    if (!confirm('هل أنت متأكد من قبول هذا التحويل؟')) {
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

    // Remove the processed transfer from the list
    transfers.value = transfers.value.filter(t => t.id !== transferId);

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
    rejectionError.value = 'سبب الرفض مطلوب.';
    return;
  }
  processTransfer(selectedTransferId.value, 'reject');
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
.pending-transfers-container {
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
  background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
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
  color: #10b981;
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

.notes {
  color: #64748b;
  font-size: 0.875rem;
  max-width: 200px;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.date {
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
  .pending-transfers-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .transfers-table-container {
    overflow-x: auto;
  }

  .action-buttons {
    flex-direction: column;
  }
}
</style>
