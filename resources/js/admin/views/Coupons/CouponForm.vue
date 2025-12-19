<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();
const toast = useToast();

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
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch coupon details',
      life: 3000
    });
    router.push('/coupons');
  } finally {
    loading.value = false;
  }
};

const generateCode = async () => {
  try {
    const response = await axios.get('/admin/coupons/generate-code');
    formData.value.code = response.data.code;
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Coupon code generated successfully',
      life: 3000
    });
  } catch (error) {
    console.error('Error generating code:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to generate coupon code',
      life: 3000
    });
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
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: isEditMode.value ? 'Coupon updated successfully!' : 'Coupon created successfully!',
        life: 3000
      });
      router.push('/coupons');
    }
  } catch (error) {
    console.error('Error saving coupon:', error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.status === 403) {
      toast.add({
        severity: 'error',
        summary: 'Access Denied',
        detail: 'You do not have permission to perform this action.',
        life: 3000
      });
      router.push('/coupons');
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save coupon',
        life: 3000
      });
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
    toast.add({
      severity: 'error',
      summary: 'Access Denied',
      detail: 'You do not have permission to access this page.',
      life: 3000
    });
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

<style scoped src="../../../../css/couponform.css"></style>
