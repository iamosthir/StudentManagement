<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const isEditMode = computed(() => !!route.params.id);
const couponId = computed(() => route.params.id);

const loading = ref(false);
const submitting = ref(false);

const formData = ref({
  code: '',
  coupon_name: '',
  discount_type: null,
  discount_value: null
});

const errors = ref({});

const discountTypeOptions = [
  { label: 'Percentage Discount', value: 'percent' },
  { label: 'Fixed Amount Discount', value: 'fixed' }
];

const adminUser = ref(JSON.parse(localStorage.getItem('admin_user')));
const isAdministrator = computed(() =>
  adminUser.value?.roles?.includes('Administrator') || false
);

const fetchCoupon = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  try {
    const response = await axios.get(`/admin/coupons/${couponId.value}`);
    const coupon = response.data.data;

    formData.value = {
      code: coupon.code,
      coupon_name: coupon.coupon_name || '',
      discount_type: coupon.discount_type,
      discount_value: coupon.discount_value
    };
  } catch (error) {
    console.error('Error fetching coupon:', error);
    alert('Failed to fetch coupon details');
    router.push('/coupons');
  } finally {
    loading.value = false;
  }
};

const generateCode = async () => {
  try {
    const response = await axios.get('/admin/coupons/generate-code');
    formData.value.code = response.data.code;
  } catch (error) {
    console.error('Error generating code:', error);
    alert('Failed to generate coupon code');
  }
};

const handleSubmit = async () => {
  errors.value = {};
  submitting.value = true;

  try {
    const payload = {
      code: formData.value.code || undefined,
      coupon_name: formData.value.coupon_name,
      discount_type: formData.value.discount_type,
      discount_value: formData.value.discount_value
    };

    let response;
    if (isEditMode.value) {
      response = await axios.put(`/admin/coupons/${couponId.value}`, payload);
    } else {
      response = await axios.post('/admin/coupons', payload);
    }

    if (response.data.success) {
      alert(isEditMode.value ? 'Coupon updated successfully!' : 'Coupon created successfully!');
      router.push('/coupons');
    }
  } catch (error) {
    console.error('Error saving coupon:', error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.status === 403) {
      alert('You do not have permission to perform this action.');
      router.push('/coupons');
    } else {
      alert(error.response?.data?.message || 'Failed to save coupon');
    }
  } finally {
    submitting.value = false;
  }
};

const handleCancel = () => {
  router.push('/coupons');
};

onMounted(() => {
  if (!isAdministrator.value) {
    alert('You do not have permission to access this page.');
    router.push('/admin/dashboard');
    return;
  }
  fetchCoupon();
});
</script>

<template>
  <div class="coupon-form-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon">
          <i class="bi bi-ticket-perforated"></i>
        </div>
        <div class="header-text">
          <h1 class="page-title">{{ isEditMode ? 'Edit Coupon' : 'Create New Coupon' }}</h1>
          <p class="page-subtitle">{{ isEditMode ? 'Update coupon details' : 'Generate a new discount coupon' }}</p>
        </div>
      </div>
      <button class="back-btn" @click="handleCancel">
        <i class="bi bi-arrow-left"></i>
        <span>Back to List</span>
      </button>
    </div>

    <!-- Form Card -->
    <div class="form-card">
      <form @submit.prevent="handleSubmit">
        <!-- Coupon Basic Information Section -->
        <div class="form-section" style="animation-delay: 0.1s">
          <div class="section-header">
            <div class="section-icon section-icon-primary">
              <i class="bi bi-info-circle"></i>
            </div>
            <div class="section-text">
              <h3 class="section-title">Basic Information</h3>
              <p class="section-subtitle">Configure coupon name and code</p>
            </div>
          </div>

          <div class="section-body">
            <div class="form-row">
              <!-- Coupon Name -->
              <div class="form-group">
                <label for="coupon_name" class="form-label">
                  <i class="bi bi-card-text"></i>
                  Coupon Name <span class="required">*</span>
                </label>
                <InputText
                  id="coupon_name"
                  v-model="formData.coupon_name"
                  placeholder="Enter a descriptive name for the coupon"
                  maxlength="255"
                  :class="{ 'p-invalid': errors.coupon_name }"
                  class="w-100"
                />
                <small v-if="errors.coupon_name" class="error-message">
                  {{ errors.coupon_name[0] }}
                </small>
              </div>

              <!-- Coupon Code -->
              <div class="form-group">
                <label for="code" class="form-label">
                  <i class="bi bi-tag"></i>
                  Coupon Code
                </label>
                <div class="code-input-group">
                  <InputText
                    id="code"
                    v-model="formData.code"
                    placeholder="Enter 5-character code or generate one"
                    maxlength="5"
                    :class="{ 'p-invalid': errors.code }"
                    class="code-input"
                  />
                  <Button
                    type="button"
                    label="Generate"
                    icon="bi bi-arrow-repeat"
                    outlined
                    @click="generateCode"
                    class="generate-btn-sm"
                  />
                </div>
                <small class="hint-text">
                  Leave empty to auto-generate a unique 5-character code
                </small>
                <small v-if="errors.code" class="error-message">
                  {{ errors.code[0] }}
                </small>
              </div>
            </div>

            <div v-if="formData.code" class="code-preview">
              <div class="code-preview-label">Code Preview:</div>
              <div class="code-preview-value">{{ formData.code }}</div>
            </div>
          </div>
        </div>

        <!-- Discount Section -->
        <div class="form-section" style="animation-delay: 0.2s">
          <div class="section-header">
            <div class="section-icon section-icon-success">
              <i class="bi bi-percent"></i>
            </div>
            <div class="section-text">
              <h3 class="section-title">Discount Details</h3>
              <p class="section-subtitle">Configure the discount type and value</p>
            </div>
          </div>

          <div class="section-body">
            <div class="form-row">
              <!-- Discount Type -->
              <div class="form-group">
                <label for="discount_type" class="form-label">
                  <i class="bi bi-bookmark"></i>
                  Discount Type <span class="required">*</span>
                </label>
                <Select
                  id="discount_type"
                  v-model="formData.discount_type"
                  :options="discountTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select discount type"
                  :class="{ 'p-invalid': errors.discount_type }"
                  class="w-100"
                />
                <small v-if="errors.discount_type" class="error-message">
                  {{ errors.discount_type[0] }}
                </small>
              </div>

              <!-- Discount Value -->
              <div class="form-group">
                <label for="discount_value" class="form-label">
                  <i class="bi bi-calculator"></i>
                  Discount Value <span class="required">*</span>
                </label>
                <InputNumber
                  v-if="formData.discount_type === 'fixed'"
                  id="discount_value"
                  v-model="formData.discount_value"
                  mode="currency"
                  currency="USD"
                  locale="en-US"
                  placeholder="Enter discount amount"
                  :class="{ 'p-invalid': errors.discount_value }"
                  class="w-100"
                />
                <InputNumber
                  v-else-if="formData.discount_type === 'percent'"
                  id="discount_value"
                  v-model="formData.discount_value"
                  mode="decimal"
                  suffix="%"
                  :min="0"
                  :max="100"
                  placeholder="Enter discount percentage"
                  :class="{ 'p-invalid': errors.discount_value }"
                  class="w-100"
                />
                <InputNumber
                  v-else
                  id="discount_value"
                  v-model="formData.discount_value"
                  mode="decimal"
                  placeholder="Select discount type first"
                  disabled
                  :class="{ 'p-invalid': errors.discount_value }"
                  class="w-100"
                />
                <small v-if="errors.discount_value" class="error-message">
                  {{ errors.discount_value[0] }}
                </small>
              </div>
            </div>

            <!-- Discount Preview -->
            <div v-if="formData.discount_type && formData.discount_value" class="discount-preview">
              <div class="preview-icon">
                <i class="bi bi-gift"></i>
              </div>
              <div class="preview-content">
                <div class="preview-label">Discount Preview</div>
                <div class="preview-value">
                  <template v-if="formData.discount_type === 'percent'">
                    {{ formData.discount_value }}% off
                  </template>
                  <template v-else>
                    ${{ formData.discount_value }} off
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <Button
            type="button"
            label="Cancel"
            severity="secondary"
            outlined
            @click="handleCancel"
            :disabled="submitting"
          />
          <Button
            type="submit"
            :label="submitting ? (isEditMode ? 'Updating...' : 'Creating...') : (isEditMode ? 'Update Coupon' : 'Create Coupon')"
            icon="bi bi-check-circle"
            :loading="submitting"
            :disabled="submitting"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.coupon-form-page {
  padding: 2rem;
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.header-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 1rem;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  color: #64748b;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.back-btn:hover {
  border-color: #8b5cf6;
  color: #8b5cf6;
  transform: translateX(-5px);
}

.form-card {
  background: white;
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.form-section {
  margin-bottom: 2.5rem;
  padding: 2rem;
  background: #f8fafc;
  border-radius: 16px;
  border: 2px solid transparent;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  animation: slideIn 0.5s ease-out forwards;
  opacity: 0;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.form-section:hover {
  border-color: #e0e7ff;
  box-shadow: 0 8px 16px -4px rgba(99, 102, 241, 0.15);
  transform: translateY(-2px);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.section-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  animation: float 3s ease-in-out infinite;
}

.section-icon-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
}

.section-icon-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.section-text {
  flex: 1;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.25rem 0;
}

.section-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.section-body {
  padding: 0;
}

.code-input-group {
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
}

.code-input {
  flex: 1;
  font-family: 'Courier New', monospace;
  font-weight: 700;
  font-size: 1.1rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.generate-btn-sm {
  white-space: nowrap;
  padding: 0.5rem 1rem;
}


.code-preview {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border: 2px solid #e0e7ff;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.code-preview-label {
  font-weight: 600;
  color: #64748b;
}

.code-preview-value {
  font-family: 'Courier New', monospace;
  font-weight: 700;
  font-size: 1.75rem;
  color: #6366f1;
  letter-spacing: 0.15em;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #334155;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.form-label i {
  color: #8b5cf6;
  font-size: 1rem;
}

.required {
  color: #ef4444;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.hint-text {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  font-style: italic;
}

.w-100 {
  width: 100%;
}

.discount-preview {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border: 2px solid #d1fae5;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.preview-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.preview-content {
  flex: 1;
}

.preview-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.preview-value {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px solid #f1f5f9;
}

@media (max-width: 768px) {
  .coupon-form-page {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .form-card {
    padding: 1.5rem;
  }

  .form-section {
    padding: 1.5rem;
  }

  .code-input-group {
    flex-direction: column;
  }

  .generate-btn-sm {
    margin-top: 0.5rem;
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions button {
    width: 100%;
  }
}
</style>
