<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const payment = ref(null);
const loading = ref(false);

const fetchPayment = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/admin/payments/${route.params.id}`);
    payment.value = response.data.data;
  } catch (error) {
    console.error('Error fetching payment:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch payment details',
      life: 3000
    });
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
    paid: 'مدفوع',
    pending: 'قيد الانتظار',
    cancelled: 'ملغى'
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
  const printUrl = `/admin/payments/${route.params.id}/print`;
  window.open(printUrl, '_blank');
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
        <p>جاري تحميل تفاصيل الدفعة...</p>
      </div>
    </div>

    <div v-else-if="payment">
      <!-- Header (hidden on print) -->
      <div class="page-header no-print">
        <div class="header-top">
          <button class="back-btn" @click="router.push('/payments')">
            <i class="bi bi-arrow-left"></i>
            <span>العودة للمدفوعات</span>
          </button>
        </div>

        <div class="header-content">
          <div class="header-icon">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="header-text">
            <h1 class="page-title">تفاصيل الدفعة</h1>
            <p class="page-subtitle">{{ payment.payment_number }}</p>
          </div>
          <div class="header-actions">
            <Button
              label="طباعة الفاتورة"
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
            <h2 class="company-name">المركز التعليمي</h2>
            <p class="company-tagline">نظام إدارة الطلاب</p>
          </div>
          <div class="invoice-meta">
            <h3 class="invoice-title">فاتورة</h3>
            <div class="invoice-number">{{ payment.payment_number }}</div>
            <div class="invoice-date">
              <span class="label">التاريخ:</span>
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
            <h4 class="party-title">فاتورة إلى:</h4>
            <div class="party-details">
              <div class="party-name">{{ payment.student?.full_name }}</div>
              <div class="party-info">رقم القبول #: {{ payment.student?.admission_number }}</div>
              <div v-if="payment.student?.phone" class="party-info">الهاتف: {{ payment.student.phone }}</div>
              <div v-if="payment.student?.email" class="party-info">البريد: {{ payment.student.email }}</div>
            </div>
          </div>

          <div class="party-section">
            <h4 class="party-title">معلومات الدفع:</h4>
            <div class="party-details">
              <div class="info-row">
                <span class="info-label">طريقة الدفع:</span>
                <span class="info-value">{{ payment.payment_method || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">تم الاستلام بواسطة:</span>
                <span class="info-value">{{ payment.admin?.name || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">التاريخ:</span>
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
                <th class="col-description">الوصف</th>
                <th class="col-center">الكمية</th>
                <th class="col-right">سعر الوحدة</th>
                <th class="col-right">الخصم</th>
                <th class="col-right">الإجمالي</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in payment.items" :key="index">
                <td class="col-description">
                  <div class="item-description">{{ item.description }}</div>
                  <div class="item-type">{{ item.item_type === 'subscription' ? 'اشتراك' : 'منتج' }}</div>
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
              <span class="total-label">المجموع الفرعي:</span>
              <span class="total-value">{{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="total-row">
              <span class="total-label">إجمالي الخصم:</span>
              <span class="total-value">{{ formatCurrency(totalDiscount) }}</span>
            </div>
            <div class="total-row grand-total">
              <span class="total-label">المبلغ الإجمالي:</span>
              <span class="total-value">{{ formatCurrency(payment.amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="payment.note" class="invoice-notes">
          <h4 class="notes-title">ملاحظات:</h4>
          <p class="notes-content">{{ payment.note }}</p>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
          <p class="footer-text">شكراً لكم على دفعتكم!</p>
          <p class="footer-signature">
            <span class="signature-line">_______________________</span><br>
            <span class="signature-label">التوقيع المعتمد</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
