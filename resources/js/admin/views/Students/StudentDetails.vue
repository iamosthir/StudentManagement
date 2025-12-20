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
    alert('Failed to fetch student details');
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
    active: 'Active',
    pending_payment: 'Pending Payment',
    expired: 'Expired',
    archived: 'Archived'
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
  <div class="student-details-page">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading student details...</p>
      </div>
    </div>

    <div v-else-if="student">
      <!-- Header -->
      <div class="page-header">
        <div class="header-top">
          <button class="back-btn" @click="router.push('/students')">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Students</span>
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
              <span>Edit Student</span>
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
            <div class="stat-label">Active Subscriptions</div>
            <div class="stat-value">{{ student.subscriptions?.filter(s => s.is_active).length || 0 }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-success">
          <div class="stat-icon">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Overall Paid Amount</div>
            <div class="stat-value">{{ formatCurrency(student.total_paid_amount || 0) }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Overall Due Amount</div>
            <div class="stat-value">{{ formatCurrency(student.total_due_amount || 0) }}</div>
          </div>
        </div>

        <div class="stat-card stat-card-info">
          <div class="stat-icon">
            <i class="bi bi-clock-history"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label">Member Since</div>
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
            <span>Overview</span>
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'subscriptions' }"
            @click="activeTab = 'subscriptions'"
          >
            <i class="bi bi-calendar-check"></i>
            <span>Subscriptions</span>
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'payments' }"
            @click="activeTab = 'payments'"
          >
            <i class="bi bi-credit-card"></i>
            <span>Payments</span>
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
                  <h3 class="info-card-title">Personal Information</h3>
                </div>
                <div class="info-list">
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-person"></i> Full Name</span>
                    <span class="info-value">{{ student.full_name }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-phone"></i> Phone</span>
                    <span class="info-value">{{ student.phone }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-envelope"></i> Email</span>
                    <span class="info-value">{{ student.email || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar"></i> Birthdate</span>
                    <span class="info-value">{{ formatDate(student.birthdate) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-gender-ambiguous"></i> Gender</span>
                    <span class="info-value">{{ student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-people"></i> Guardian</span>
                    <span class="info-value">{{ student.guardian_name || '—' }}</span>
                  </div>
                </div>
              </div>

              <div class="info-card">
                <div class="info-card-header">
                  <div class="info-icon info-icon-success">
                    <i class="bi bi-book"></i>
                  </div>
                  <h3 class="info-card-title">Academic Information</h3>
                </div>
                <div class="info-list">
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-mortarboard"></i> Program</span>
                    <span class="info-value">{{ student.program?.name || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar3"></i> Academic Year</span>
                    <span class="info-value">{{ student.academic_year || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-diagram-3"></i> Class Section</span>
                    <span class="info-value">{{ student.class_section || '—' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar-x"></i> Last Subscription Expiry</span>
                    <span class="info-value">{{ formatDate(student.last_subscription_expiry) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label"><i class="bi bi-calendar-plus"></i> Registration Date</span>
                    <span class="info-value">{{ formatDate(student.created_at) }}</span>
                  </div>
                </div>
              </div>

              <div v-if="student.address" class="info-card info-card-full">
                <div class="info-card-header">
                  <div class="info-icon info-icon-warning">
                    <i class="bi bi-geo-alt"></i>
                  </div>
                  <h3 class="info-card-title">Address</h3>
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
              <h3 class="tab-title">Subscription History</h3>
              <button class="btn-add" @click="router.push(`/subscriptions/create?student_id=${student.id}`)">
                <i class="bi bi-plus-circle"></i>
                <span>Add Subscription</span>
              </button>
            </div>

            <div v-if="student.subscriptions && student.subscriptions.length > 0" class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Program</th>
                    <th>Option</th>
                    <th>Start Date</th>
                    <th>Expiry Date</th>
                    <th>Price</th>
                    <th>Status</th>
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
                        {{ subscription.is_active ? 'Active' : 'Expired' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="empty-state">
              <i class="bi bi-calendar-x"></i>
              <p>No subscriptions found</p>
            </div>
          </div>

          <!-- Payments Tab -->
          <div v-if="activeTab === 'payments'" class="tab-pane">
            <div class="tab-header">
              <h3 class="tab-title">Payment History</h3>
              <button class="btn-add" @click="router.push(`/payments/create?student_id=${student.id}`)">
                <i class="bi bi-plus-circle"></i>
                <span>Add Payment</span>
              </button>
            </div>

            <div v-if="student.payments && student.payments.length > 0" class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Payment Number</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                        {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                      </span>
                    </td>
                    <td>
                      <button
                        class="btn-icon"
                        @click="router.push(`/payments/${payment.id}`)"
                        title="View"
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
              <p>No payments found</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
