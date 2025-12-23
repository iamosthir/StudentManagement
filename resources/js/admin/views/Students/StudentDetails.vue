<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();

const student = ref(null);
const loading = ref(false);
const activeTab = ref('overview');

const fetchStudent = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/admin/students/${route.params.id}`);
    student.value = response.data.data;
  } catch (error) {
    console.error('Error fetching student:', error);
    alert('فشل في جلب تفاصيل الطالب');
    router.push('/students');
  } finally {
    loading.value = false;
  }
};

const getStatusClass = (status) => {
  const classes = {
    active: 'success',
    pending_payment: 'warning',
    expired: 'danger',
    archived: 'secondary'
  };
  return classes[status] || 'secondary';
};

const getStatusLabel = (status) => {
  const labels = {
    active: 'نشط',
    pending_payment: 'انتظار الدفع',
    expired: 'منتهي',
    archived: 'مؤرشف'
  };
  return labels[status] || status;
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

onMounted(() => {
  fetchStudent();
});
</script>

<template>
  <div class="student-details-page" dir="rtl">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>جاري تحميل تفاصيل الطالب...</p>
      </div>
    </div>

    <div v-else-if="student">
      <!-- Header -->
      <div class="page-header">
        <div class="header-top">
          <button class="back-btn" @click="router.push('/students')">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للطلاب</span>
          </button>
        </div>

        <div class="header-content">
          <div class="student-avatar">
            <i class="bi bi-person-circle"></i>
          </div>
          <div class="header-text">
            <h1 class="page-title">{{ student.full_name }}</h1>
            <div class="student-meta">
              <span class="admission-badge">
                <i class="bi bi-hash"></i>
                {{ student.admission_number }}
              </span>
              <span class="status-badge" :class="`status-${getStatusClass(student.status)}`">
                <i class="bi bi-circle-fill"></i>
                {{ getStatusLabel(student.status) }}
              </span>
            </div>
          </div>
          <div class="header-actions">
            <button class="action-btn btn-edit" @click="router.push(`/students/${student.id}/edit`)">
              <i class="bi bi-pencil"></i>
              <span>تعديل الطالب</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card stat-card-primary">
          <div class="stat-icon">
            <i class="bi bi-calendar-check-fill"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">الاشتراكات النشطة</div>
            <div class="stat-value">{{ student.subscriptions?.filter(s => s.is_active).length || 0 }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-success">
          <div class="stat-icon">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">إجمالي المبلغ المدفوع</div>
            <div class="stat-value">{{ formatCurrency(student.total_paid_amount || 0) }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">إجمالي المبلغ المستحق</div>
            <div class="stat-value">{{ formatCurrency(student.total_due_amount || 0) }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-info">
          <div class="stat-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">تاريخ الانضمام</div>
            <div class="stat-value">{{ formatDate(student.created_at) }}</div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-container">
        <div class="tabs-header">
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'overview' }"
            @click="activeTab = 'overview'"
          >
            <i class="bi bi-person-circle"></i>
            <span>نظرة عامة</span>
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'subscriptions' }"
            @click="activeTab = 'subscriptions'"
          >
            <i class="bi bi-calendar-check"></i>
            <span>الاشتراكات</span>
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'payments' }"
            @click="activeTab = 'payments'"
          >
            <i class="bi bi-credit-card"></i>
            <span>المدفوعات</span>
          </button>
        </div>

        <div class="tabs-content">
          <!-- Overview Tab -->
          <div v-if="activeTab === 'overview'" class="tab-pane">
            <div class="info-cards-grid">
              <div class="info-card">
                <div class="info-card-header">
                  <div class="info-icon info-icon-primary">
                    <i class="bi bi-person-badge"></i>
                  </div>
                  <h3 class="info-card-title">المعلومات الشخصية</h3>
                </div>
                <div class="info-list">
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-person"></i> الاسم الكامل</span>
                    <span class="info-value">{{ student.full_name }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-phone"></i> الهاتف</span>
                    <span class="info-value">{{ student.phone }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-envelope"></i> البريد الإلكتروني</span>
                    <span class="info-value">{{ student.email || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar"></i> تاريخ الميلاد</span>
                    <span class="info-value">{{ formatDate(student.birthdate) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-gender-ambiguous"></i> الجنس</span>
                    <span class="info-value">{{ student.gender ? (student.gender === 'male' ? 'ذكر' : 'أنثى') : '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-people"></i> ولي الأمر</span>
                    <span class="info-value">{{ student.guardian_name || '—' }}</span>
                  </div>
                </div>
              </div>

              <div class="info-card">
                <div class="info-card-header">
                  <div class="info-icon info-icon-success">
                    <i class="bi bi-book"></i>
                  </div>
                  <h3 class="info-card-title">المعلومات الأكاديمية</h3>
                </div>
                <div class="info-list">
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-mortarboard"></i> البرنامج</span>
                    <span class="info-value">{{ student.program?.name || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar3"></i> السنة الأكاديمية</span>
                    <span class="info-value">{{ student.academic_year || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-diagram-3"></i> الشعبة</span>
                    <span class="info-value">{{ student.class_section || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar-x"></i> تاريخ انتهاء آخر اشتراك</span>
                    <span class="info-value">{{ formatDate(student.last_subscription_expiry) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar-plus"></i> تاريخ التسجيل</span>
                    <span class="info-value">{{ formatDate(student.created_at) }}</span>
                  </div>
                </div>
              </div>

              <div v-if="student.address" class="info-card info-card-full">
                <div class="info-card-header">
                  <div class="info-icon info-icon-warning">
                    <i class="bi bi-geo-alt"></i>
                  </div>
                  <h3 class="info-card-title">العنوان</h3>
                </div>
                <div class="info-list">
                  <p class="address-text">{{ student.address }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Subscriptions Tab -->
          <div v-if="activeTab === 'subscriptions'" class="tab-pane">
            <div class="tab-header">
              <h3 class="tab-title">سجل الاشتراكات</h3>
              <button class="btn-add" @click="router.push(`/subscriptions/create?student_id=${student.id}`)">
                <i class="bi bi-plus-circle"></i>
                <span>إضافة اشتراك</span>
              </button>
            </div>

            <div v-if="student.subscriptions && student.subscriptions.length > 0" class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>البرنامج</th>
                    <th>الخيار</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>السعر</th>
                    <th>الحالة</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="subscription in student.subscriptions" :key="subscription.id">
                    <td>{{ subscription.program?.name }}</td>
                    <td>{{ subscription.subscription_option?.name }}</td>
                    <td>{{ formatDate(subscription.start_date) }}</td>
                    <td>{{ formatDate(subscription.expiry_date) }}</td>
                    <td>{{ formatCurrency(subscription.final_price) }}</td>
                    <td>
                      <span
                        class="status-badge"
                        :class="subscription.is_active ? 'status-success' : 'status-danger'"
                      >
                        {{ subscription.is_active ? 'نشط' : 'منتهي' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="empty-state">
              <i class="bi bi-calendar-x"></i>
              <p>لم يتم العثور على اشتراكات</p>
            </div>
          </div>

          <!-- Payments Tab -->
          <div v-if="activeTab === 'payments'" class="tab-pane">
            <div class="tab-header">
              <h3 class="tab-title">سجل المدفوعات</h3>
              <button class="btn-add" @click="router.push(`/payments/create?student_id=${student.id}`)">
                <i class="bi bi-plus-circle"></i>
                <span>إضافة دفعة</span>
              </button>
            </div>

            <div v-if="student.payments && student.payments.length > 0" class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>رقم الدفعة</th>
                    <th>التاريخ</th>
                    <th>المبلغ</th>
                    <th>الطريقة</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="payment in student.payments" :key="payment.id">
                    <td>{{ payment.payment_number }}</td>
                    <td>{{ formatDate(payment.paid_at || payment.created_at) }}</td>
                    <td>{{ formatCurrency(payment.amount) }}</td>
                    <td>{{ payment.payment_method || '—' }}</td>
                    <td>
                      <span
                        class="status-badge"
                        :class="`status-${payment.status === 'paid' ? 'success' : payment.status === 'pending' ? 'warning' : 'danger'}`"
                      >
                        {{ payment.status === 'paid' ? 'مدفوع' : payment.status === 'pending' ? 'معلق' : 'فاشل' }}
                      </span>
                    </td>
                    <td>
                      <button
                        class="btn-icon"
                        @click="router.push(`/payments/${payment.id}`)"
                        title="عرض"
                      >
                        <i class="bi bi-eye"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="empty-state">
              <i class="bi bi-credit-card-2-front"></i>
              <p>لم يتم العثور على مدفوعات</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
