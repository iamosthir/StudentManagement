<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const payment = ref(null);
const loading = ref(false);

const fetchPayment = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/admin/payments/${route.params.id}`);
    payment.value = response.data.data;
  } catch (error) {
    console.error('Error fetching payment:', error);
    alert('Failed to fetch payment details');
    router.push('/payments');
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
};

const getStatusClass = (status) => {
  const classes = {
    paid: 'success',
    pending: 'warning',
    cancelled: 'danger'
  };
  return classes[status] || 'secondary';
};

const getStatusLabel = (status) => {
  const labels = {
    paid: 'Paid',
    pending: 'Pending',
    cancelled: 'Cancelled'
  };
  return labels[status] || status;
};

const subtotal = computed(() => {
  if (!payment.value?.items) return 0;
  return payment.value.items.reduce((sum, item) => {
    return sum + (parseFloat(item.unit_price) * parseInt(item.quantity));
  }, 0);
});

const totalDiscount = computed(() => {
  if (!payment.value?.items) return 0;
  return payment.value.items.reduce((sum, item) => {
    return sum + parseFloat(item.discount_value || 0);
  }, 0);
});

const grandTotal = computed(() => {
  return subtotal.value - totalDiscount.value;
});

const printInvoice = () => {
  window.print();
};

onMounted(() => {
  fetchPayment();
});
</script>

<template>
  <div class="payment-details-page">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading payment details...</p>
      </div>
    </div>

    <div v-else-if="payment">
      <!-- Header (hidden on print) -->
      <div class="page-header no-print">
        <div class="header-top">
          <button class="back-btn" @click="router.push('/payments')">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Payments</span>
          </button>
        </div>

        <div class="header-content">
          <div class="header-icon">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="header-text">
            <h1 class="page-title">Payment Details</h1>
            <p class="page-subtitle">{{ payment.payment_number }}</p>
          </div>
          <div class="header-actions">
            <Button
              label="Print Invoice"
              icon="bi bi-printer"
              severity="primary"
              @click="printInvoice"
            />
          </div>
        </div>
      </div>

      <!-- Invoice Content -->
      <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
          <div class="company-info">
            <h2 class="company-name">Educational Center</h2>
            <p class="company-tagline">Student Management System</p>
          </div>
          <div class="invoice-meta">
            <h3 class="invoice-title">INVOICE</h3>
            <div class="invoice-number">{{ payment.payment_number }}</div>
            <div class="invoice-date">
              <span class="label">Date:</span>
              <span class="value">{{ formatDate(payment.paid_at || payment.created_at) }}</span>
            </div>
            <div class="invoice-status">
              <span class="status-badge" :class="`status-${getStatusClass(payment.status)}`">
                {{ getStatusLabel(payment.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Bill To / Payment Info -->
        <div class="invoice-parties">
          <div class="party-section">
            <h4 class="party-title">Bill To:</h4>
            <div class="party-details">
              <div class="party-name">{{ payment.student?.full_name }}</div>
              <div class="party-info">Admission #: {{ payment.student?.admission_number }}</div>
              <div v-if="payment.student?.phone" class="party-info">Phone: {{ payment.student.phone }}</div>
              <div v-if="payment.student?.email" class="party-info">Email: {{ payment.student.email }}</div>
            </div>
          </div>

          <div class="party-section">
            <h4 class="party-title">Payment Information:</h4>
            <div class="party-details">
              <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">{{ payment.payment_method || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Received By:</span>
                <span class="info-value">{{ payment.admin?.name || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ formatDate(payment.paid_at || payment.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Items Table -->
        <div class="invoice-items">
          <table class="items-table">
            <thead>
              <tr>
                <th class="col-description">Description</th>
                <th class="col-center">Quantity</th>
                <th class="col-right">Unit Price</th>
                <th class="col-right">Discount</th>
                <th class="col-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in payment.items" :key="index">
                <td class="col-description">
                  <div class="item-description">{{ item.description }}</div>
                  <div class="item-type">{{ item.item_type === 'subscription' ? 'Subscription' : 'Product' }}</div>
                </td>
                <td class="col-center">{{ item.quantity }}</td>
                <td class="col-right">{{ formatCurrency(item.unit_price) }}</td>
                <td class="col-right">{{ formatCurrency(item.discount_value) }}</td>
                <td class="col-right">{{ formatCurrency(item.total_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Totals -->
        <div class="invoice-totals">
          <div class="totals-rows">
            <div class="total-row">
              <span class="total-label">Subtotal:</span>
              <span class="total-value">{{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="total-row">
              <span class="total-label">Total Discount:</span>
              <span class="total-value">{{ formatCurrency(totalDiscount) }}</span>
            </div>
            <div class="total-row grand-total">
              <span class="total-label">Grand Total:</span>
              <span class="total-value">{{ formatCurrency(payment.amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="payment.note" class="invoice-notes">
          <h4 class="notes-title">Notes:</h4>
          <p class="notes-content">{{ payment.note }}</p>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
          <p class="footer-text">Thank you for your payment!</p>
          <p class="footer-signature">
            <span class="signature-line">_______________________</span><br>
            <span class="signature-label">Authorized Signature</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.payment-details-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

/* Loading State */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.loading-spinner {
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  margin: 0 auto 20px;
  border: 4px solid #e9ecef;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.header-top {
  margin-bottom: 16px;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
  transform: translateX(-4px);
}

.header-content {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-radius: 16px;
  color: white;
}

.header-icon {
  font-size: 48px;
  opacity: 0.9;
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 4px;
}

.page-subtitle {
  font-size: 16px;
  opacity: 0.9;
  margin: 0;
}

.header-actions {
  margin-left: auto;
}

/* Invoice Container */
.invoice-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  padding: 40px;
  max-width: 900px;
  margin: 0 auto;
}

/* Invoice Header */
.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  padding-bottom: 20px;
  border-bottom: 2px solid #333;
}

.company-name {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
}

.company-tagline {
  color: #64748b;
  font-size: 13px;
  margin: 0;
}

.invoice-meta {
  text-align: right;
}

.invoice-title {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px;
}

.invoice-number {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
}

.invoice-date {
  margin-bottom: 8px;
  color: #64748b;
  font-size: 14px;
}

.invoice-date .label {
  font-weight: 600;
  margin-right: 8px;
}

/* Invoice Parties */
.invoice-parties {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 30px;
}

.party-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: #1e293b;
  margin: 0 0 12px;
  letter-spacing: 0.5px;
}

.party-name {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
}

.party-info {
  color: #64748b;
  margin-bottom: 3px;
  font-size: 14px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
  font-size: 14px;
}

.info-label {
  font-weight: 600;
  color: #64748b;
}

.info-value {
  color: #1e293b;
}

/* Items Table */
.invoice-items {
  margin-bottom: 25px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table thead {
  background: #f5f5f5;
}

.items-table th {
  padding: 12px;
  text-align: left;
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
  color: #333;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #333;
}

.items-table td {
  padding: 12px;
  border-bottom: 1px solid #ddd;
  color: #1e293b;
  font-size: 14px;
}

.items-table tbody tr:last-child td {
  border-bottom: 2px solid #333;
}

.col-description {
  width: 40%;
}

.col-center {
  text-align: center;
  width: 15%;
}

.col-right {
  text-align: right;
  width: 15%;
}

.item-description {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
}

.item-type {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 500;
}

/* Totals */
.invoice-totals {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 35px;
}

.totals-rows {
  width: 300px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #ddd;
  font-size: 14px;
}

.total-row.grand-total {
  background: #f5f5f5;
  color: #1e293b;
  padding: 14px;
  border-radius: 4px;
  margin-top: 8px;
  border-bottom: none;
  border: 2px solid #333;
  font-size: 16px;
  font-weight: 700;
}

.total-label {
  font-weight: 600;
}

.total-value {
  font-weight: 700;
}

/* Notes */
.invoice-notes {
  margin-bottom: 35px;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 4px;
  border-left: 3px solid #333;
}

.notes-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: #333;
  margin: 0 0 8px;
}

.notes-content {
  color: #64748b;
  margin: 0;
  line-height: 1.6;
  font-size: 14px;
}

/* Footer */
.invoice-footer {
  text-align: center;
  padding-top: 30px;
  border-top: 2px solid #333;
}

.footer-text {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 25px;
}

.footer-signature {
  display: inline-block;
  text-align: center;
}

.signature-line {
  display: inline-block;
  min-width: 200px;
  border-bottom: 2px solid #333;
  margin-bottom: 6px;
}

.signature-label {
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  border: 1px solid #333;
}

.status-success {
  background: #e8f5e9;
  color: #1b5e20;
}

.status-warning {
  background: #fff9e6;
  color: #663c00;
}

.status-danger {
  background: #ffebee;
  color: #b71c1c;
}

/* Print Styles */
@media print {
  /* Hide all non-invoice elements */
  .no-print,
  .page-header,
  .header-top,
  .header-content,
  .header-actions,
  nav,
  .sidebar,
  .navbar,
  .menu,
  header,
  footer,
  .back-btn,
  button {
    display: none !important;
  }

  .payment-details-page {
    padding: 0;
    margin: 0;
  }

  .invoice-container {
    box-shadow: none;
    padding: 20px;
    border-radius: 0;
    max-width: 100%;
    margin: 0;
  }

  /* Simplify colors for print */
  .company-name {
    color: #000 !important;
    -webkit-text-fill-color: #000 !important;
  }

  .invoice-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #000;
  }

  .invoice-parties {
    margin-bottom: 30px;
  }

  .party-title {
    color: #000 !important;
  }

  .items-table thead {
    background: #f0f0f0 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .items-table th {
    border-bottom: 2px solid #000 !important;
  }

  .total-row.grand-total {
    background: #f0f0f0 !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    border: 2px solid #000 !important;
  }

  .invoice-notes {
    background: #f9f9f9 !important;
    border-left: 3px solid #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .status-badge {
    border: 1px solid #000 !important;
  }

  @page {
    margin: 1.5cm;
    size: A4;
  }

  /* Force hide all app layout elements */
  #app > *:not(.payment-details-page) {
    display: none !important;
  }

  /* Hide everything in body except our page */
  body > *:not(#app) {
    display: none !important;
  }

  /* Ensure only invoice shows */
  .payment-details-page > *:not(.invoice-container) {
    display: none !important;
  }
}
</style>
