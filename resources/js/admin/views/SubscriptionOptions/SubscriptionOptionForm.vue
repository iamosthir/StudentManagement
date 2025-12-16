<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const errors = ref({});

const form = ref({
  name: '',
  price: null,
  duration_months: null,
  is_full_payment: true,
  is_active: true
});

const paymentTypeOptions = [
  { label: 'Full Payment', value: true },
  { label: 'Installment', value: false }
];

const loadOption = async () => {
  try {
    const response = await axios.get(`/admin/subscription-options/${route.params.id}`);
    const option = response.data.data;

    form.value = {
      name: option.name,
      price: parseFloat(option.price),
      duration_months: option.duration_months,
      is_full_payment: option.is_full_payment,
      is_active: option.is_active
    };
  } catch (error) {
    console.error('Error loading subscription option:', error);
    alert('Failed to load subscription option');
    router.push('/subscription-options');
  }
};

const submitForm = async () => {
  errors.value = {};
  loading.value = true;

  try {
    const url = isEdit.value
      ? `/admin/subscription-options/${route.params.id}`
      : '/admin/subscription-options';

    const method = isEdit.value ? 'put' : 'post';

    await axios[method](url, form.value);

    router.push('/subscription-options');
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    } else {
      console.error('Error submitting form:', error);
      alert('Failed to save subscription option');
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (isEdit.value) {
    loadOption();
  }
});
</script>

<template>
  <div class="subscription-option-form">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-wrapper">
        <div class="header-text">
          <h1 class="page-title">
            <i class="bi bi-bookmark-fill me-3"></i>
            {{ isEdit ? 'Edit Subscription Option' : 'Add New Subscription Option' }}
          </h1>
          <p class="page-subtitle">
            {{ isEdit ? 'Update subscription option details' : 'Create a new subscription pricing plan' }}
          </p>
        </div>
        <Button
          label="Back to List"
          icon="bi bi-arrow-right"
          iconPos="right"
          outlined
          severity="secondary"
          @click="router.push('/subscription-options')"
          class="back-btn"
        />
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm" class="subscription-option-form-content">
      <!-- Basic Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="bi bi-info-circle"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">Basic Information</h3>
            <p class="section-subtitle">Subscription option name and status</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-md-8">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-tag me-2"></i>
                  Option Name
                  <span class="required">*</span>
                </label>
                <InputText
                  v-model="form.name"
                  :invalid="!!errors.name"
                  placeholder="e.g., Monthly, Quarterly, Annual"
                  class="w-100"
                />
                <small v-if="errors.name" class="error-message">
                  {{ errors.name[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-toggle-on me-2"></i>
                  Status
                </label>
                <div class="toggle-field">
                  <ToggleSwitch v-model="form.is_active" />
                  <span class="toggle-label">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pricing & Duration -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-success">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">Pricing & Duration</h3>
            <p class="section-subtitle">Define price and subscription period</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-currency-dollar me-2"></i>
                  Price
                  <span class="required">*</span>
                </label>
                <InputNumber
                  v-model="form.price"
                  :invalid="!!errors.price"
                  mode="currency"
                  currency="USD"
                  locale="en-US"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  placeholder="0.00"
                  class="w-100"
                  inputClass="w-100"
                />
                <small v-if="errors.price" class="error-message">
                  {{ errors.price[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-calendar-range me-2"></i>
                  Duration (Months)
                  <span class="required">*</span>
                </label>
                <InputNumber
                  v-model="form.duration_months"
                  :invalid="!!errors.duration_months"
                  :min="1"
                  :max="120"
                  placeholder="Enter number of months"
                  class="w-100"
                  inputClass="w-100"
                />
                <small class="field-hint">
                  Number of months this subscription lasts (1-120)
                </small>
                <small v-if="errors.duration_months" class="error-message">
                  {{ errors.duration_months[0] }}
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Type -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-warning">
            <i class="bi bi-credit-card"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">Payment Type</h3>
            <p class="section-subtitle">How students can pay for this subscription</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-12">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-wallet2 me-2"></i>
                  Payment Method
                  <span class="required">*</span>
                </label>
                <Select
                  v-model="form.is_full_payment"
                  :options="paymentTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  :invalid="!!errors.is_full_payment"
                  placeholder="Select payment type"
                  class="w-100"
                />
                <small class="field-hint">
                  {{ form.is_full_payment ? 'Students must pay the full amount upfront' : 'Students can pay in installments over time' }}
                </small>
                <small v-if="errors.is_full_payment" class="error-message">
                  {{ errors.is_full_payment[0] }}
                </small>
              </div>
            </div>

            <!-- Summary Card -->
            <div class="col-12">
              <div class="summary-card">
                <div class="summary-icon">
                  <i class="bi bi-info-circle"></i>
                </div>
                <div class="summary-content">
                  <h4 class="summary-title">Subscription Summary</h4>
                  <div class="summary-details">
                    <div class="summary-item">
                      <span class="summary-label">Price:</span>
                      <span class="summary-value">${{ form.price?.toFixed(2) || '0.00' }}</span>
                    </div>
                    <div class="summary-item">
                      <span class="summary-label">Duration:</span>
                      <span class="summary-value">{{ form.duration_months || 0 }} {{ form.duration_months === 1 ? 'month' : 'months' }}</span>
                    </div>
                    <div class="summary-item">
                      <span class="summary-label">Payment Type:</span>
                      <span class="summary-value">{{ form.is_full_payment ? 'Full Payment' : 'Installment' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Buttons -->
      <div class="form-actions">
        <Button
          label="Cancel"
          icon="bi bi-x-lg"
          outlined
          severity="secondary"
          @click="router.push('/subscription-options')"
          type="button"
        />
        <Button
          :label="isEdit ? 'Update Option' : 'Create Option'"
          :icon="loading ? 'pi pi-spin pi-spinner' : 'bi bi-check-lg'"
          :loading="loading"
          type="submit"
          class="submit-btn"
        />
      </div>
    </form>
  </div>
</template>

<style scoped>
.subscription-option-form {
  padding-bottom: 3rem;
  animation: fadeIn 0.5s ease-out;
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

/* Page Header */
.page-header {
  margin-bottom: 2.5rem;
}

.header-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  flex-wrap: wrap;
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
}

.page-title i {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

.page-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

/* Form Sections */
.form-section {
  background: #ffffff;
  border-radius: 24px;
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  animation: scaleIn 0.5s ease-out;
  animation-fill-mode: backwards;
}

.form-section:nth-child(2) { animation-delay: 0.1s; }
.form-section:nth-child(3) { animation-delay: 0.2s; }
.form-section:nth-child(4) { animation-delay: 0.3s; }

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.form-section:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(99, 102, 241, 0.12);
  border-color: #6366f1;
}

.section-header {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
  border-bottom: 1px solid #e0e7ff;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.section-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  flex-shrink: 0;
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.section-icon-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

.section-icon-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
}

.form-section:hover .section-icon {
  transform: scale(1.1) rotate(-5deg);
}

.section-title-wrapper {
  flex: 1;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 0.375rem 0;
}

.section-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.section-body {
  padding: 2rem;
}

/* Form Fields */
.form-field {
  margin-bottom: 0;
}

.field-label {
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 0.9375rem;
  color: #475569;
  margin-bottom: 0.625rem;
}

.field-label i {
  color: #6366f1;
  font-size: 1rem;
}

.required {
  color: #ef4444;
  margin-left: 0.25rem;
}

.field-hint {
  display: block;
  margin-top: 0.375rem;
  color: #94a3b8;
  font-size: 0.8125rem;
}

.error-message {
  display: block;
  margin-top: 0.375rem;
  color: #ef4444;
  font-size: 0.8125rem;
  font-weight: 500;
}

.toggle-field {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 0;
}

.toggle-label {
  font-weight: 600;
  color: #475569;
  font-size: 0.9375rem;
}

/* Summary Card */
.summary-card {
  display: flex;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
  border-radius: 16px;
  border: 2px solid #e0e7ff;
  transition: all 0.3s ease;
}

.summary-card:hover {
  border-color: #6366f1;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
}

.summary-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.summary-content {
  flex: 1;
}

.summary-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1rem 0;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #dbeafe;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-label {
  font-weight: 600;
  color: #64748b;
  font-size: 0.875rem;
}

.summary-value {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.9375rem;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 2rem;
}

.submit-btn {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
  border: none !important;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3) !important;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
}

.submit-btn:hover {
  transform: translateY(-2px) scale(1.02) !important;
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4) !important;
}

/* PrimeVue Component Overrides */
:deep(.p-inputtext),
:deep(.p-select),
:deep(.p-inputnumber-input) {
  border-radius: 12px !important;
  border: 2px solid #e2e8f0 !important;
  padding: 0.75rem 1rem !important;
  font-size: 0.9375rem !important;
  transition: all 0.3s ease !important;
}

:deep(.p-inputtext:focus),
:deep(.p-select:focus),
:deep(.p-inputnumber:focus-within) {
  border-color: #6366f1 !important;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
}

:deep(.p-inputtext.p-invalid),
:deep(.p-select.p-invalid),
:deep(.p-inputnumber.p-invalid .p-inputnumber-input) {
  border-color: #ef4444 !important;
}

:deep(.p-button) {
  border-radius: 12px !important;
  padding: 0.75rem 1.5rem !important;
  font-weight: 600 !important;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
}

:deep(.p-button:hover) {
  transform: translateY(-2px) !important;
}

:deep(.p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider) {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
}

/* Responsive */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.5rem;
  }

  .section-header {
    padding: 1.5rem;
  }

  .section-body {
    padding: 1.5rem;
  }

  .summary-card {
    flex-direction: column;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions :deep(.p-button) {
    width: 100%;
  }
}
</style>
