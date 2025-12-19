<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const submitting = ref(false);
const students = ref([]);
const programs = ref([]);
const subscriptionOptions = ref([]);
const filteredSubscriptionOptions = ref([]);

const formData = ref({
  student_id: null,
  program_id: null,
  subscription_option_id: null,
  custom_price: null,
  discount_type: null,
  discount_value: null,
  coupon_code: '',
  start_date: new Date()
});

const errors = ref({});

const discountTypeOptions = [
  { label: 'No Discount', value: null },
  { label: 'Percentage', value: 'percent' },
  { label: 'Fixed Amount', value: 'fixed' }
];

// Pre-fill student_id from query parameter
onMounted(() => {
  const studentIdParam = route.query.student_id;
  if (studentIdParam) {
    formData.value.student_id = parseInt(studentIdParam);
  }
  fetchStudents();
  fetchPrograms();
  fetchSubscriptionOptions();
});

// Watch program_id to filter subscription options
watch(() => formData.value.program_id, (newProgramId) => {
  if (newProgramId) {
    filterSubscriptionOptions(newProgramId);
  } else {
    filteredSubscriptionOptions.value = [];
    formData.value.subscription_option_id = null;
  }
});

const fetchStudents = async () => {
  try {
    const response = await axios.get('/admin/students', { params: { per_page: 1000 } });
    students.value = response.data.data.map(student => ({
      label: `${student.admission_number} - ${student.full_name}`,
      value: student.id
    }));
  } catch (error) {
    console.error('Error fetching students:', error);
  }
};

const fetchPrograms = async () => {
  try {
    const response = await axios.get('/admin/programs', { params: { per_page: 1000 } });
    programs.value = response.data.data.map(program => ({
      label: program.name,
      value: program.id
    }));
  } catch (error) {
    console.error('Error fetching programs:', error);
  }
};

const fetchSubscriptionOptions = async () => {
  try {
    const response = await axios.get('/admin/subscription-options', { params: { per_page: 1000 } });
    subscriptionOptions.value = response.data.data;
  } catch (error) {
    console.error('Error fetching subscription options:', error);
  }
};

const filterSubscriptionOptions = async (programId) => {
  try {
    const response = await axios.get(`/admin/programs/${programId}`);
    const program = response.data.data;

    if (program.subscription_options && program.subscription_options.length > 0) {
      filteredSubscriptionOptions.value = program.subscription_options.map(option => ({
        label: `${option.name} - ${option.duration_months} months - $${option.price}`,
        value: option.id,
        price: option.price,
        duration: option.duration_months
      }));
    } else {
      filteredSubscriptionOptions.value = [];
    }
  } catch (error) {
    console.error('Error fetching program subscription options:', error);
    filteredSubscriptionOptions.value = [];
  }
};

const selectedSubscriptionOption = computed(() => {
  return filteredSubscriptionOptions.value.find(opt => opt.value === formData.value.subscription_option_id);
});

const effectivePrice = computed(() => {
  const basePrice = formData.value.custom_price || selectedSubscriptionOption.value?.price || 0;

  if (!formData.value.discount_value || !formData.value.discount_type) {
    return basePrice;
  }

  if (formData.value.discount_type === 'percent') {
    return basePrice - (basePrice * formData.value.discount_value / 100);
  }

  return basePrice - formData.value.discount_value;
});

const handleSubmit = async () => {
  errors.value = {};
  submitting.value = true;

  try {
    const payload = {
      student_id: formData.value.student_id,
      program_id: formData.value.program_id,
      subscription_option_id: formData.value.subscription_option_id,
      custom_price: formData.value.custom_price || null,
      discount_type: formData.value.discount_type || null,
      discount_value: formData.value.discount_value || null,
      coupon_code: formData.value.coupon_code || null,
      start_date: formData.value.start_date.toISOString().split('T')[0]
    };

    const response = await axios.post('/admin/subscriptions', payload);

    if (response.data.success) {
      alert('Subscription created successfully!');

      // Redirect to student details if came from there
      if (route.query.student_id) {
        router.push(`/students/${route.query.student_id}`);
      } else {
        router.push('/subscriptions');
      }
    }
  } catch (error) {
    console.error('Error creating subscription:', error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      alert(error.response?.data?.message || 'Failed to create subscription');
    }
  } finally {
    submitting.value = false;
  }
};

const handleCancel = () => {
  if (route.query.student_id) {
    router.push(`/students/${route.query.student_id}`);
  } else {
    router.push('/subscriptions');
  }
};
</script>

<template>
  <div class="subscription-form-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon">
          <i class="bi bi-calendar-plus"></i>
        </div>
        <div class="header-text">
          <h1 class="page-title">Add New Subscription</h1>
          <p class="page-subtitle">Create a new subscription for a student</p>
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
        <!-- Student & Program Section -->
        <div class="form-section" style="animation-delay: 0.1s">
          <div class="section-header">
            <div class="section-icon section-icon-primary">
              <i class="bi bi-person-badge"></i>
            </div>
            <div class="section-text">
              <h3 class="section-title">Student & Program Details</h3>
              <p class="section-subtitle">Select the student and program for this subscription</p>
            </div>
          </div>

          <div class="section-body">
            <div class="form-row">
              <!-- Student -->
              <div class="form-group">
                <label for="student_id" class="form-label">
                  <i class="bi bi-person"></i>
                  Student <span class="required">*</span>
                </label>
                <Select
                  id="student_id"
                  v-model="formData.student_id"
                  :options="students"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select a student"
                  filter
                  :class="{ 'p-invalid': errors.student_id }"
                  :disabled="!!route.query.student_id"
                  class="w-100"
                />
                <small v-if="errors.student_id" class="error-message">
                  {{ errors.student_id[0] }}
                </small>
              </div>

              <!-- Program -->
              <div class="form-group">
                <label for="program_id" class="form-label">
                  <i class="bi bi-mortarboard"></i>
                  Program <span class="required">*</span>
                </label>
                <Select
                  id="program_id"
                  v-model="formData.program_id"
                  :options="programs"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select a program"
                  filter
                  :class="{ 'p-invalid': errors.program_id }"
                  class="w-100"
                />
                <small v-if="errors.program_id" class="error-message">
                  {{ errors.program_id[0] }}
                </small>
              </div>
            </div>

            <!-- Subscription Option -->
            <div class="form-group mb-4">
              <label for="subscription_option_id" class="form-label">
                <i class="bi bi-calendar-check"></i>
                Subscription Option <span class="required">*</span>
              </label>
              <Select
                id="subscription_option_id"
                v-model="formData.subscription_option_id"
                :options="filteredSubscriptionOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select a subscription option"
                :disabled="!formData.program_id"
                :class="{ 'p-invalid': errors.subscription_option_id }"
                class="w-100"
              />
              <small v-if="!formData.program_id" class="hint-text">
                Please select a program first
              </small>
              <small v-else-if="filteredSubscriptionOptions.length === 0" class="hint-text">
                No subscription options available for this program
              </small>
              <small v-if="errors.subscription_option_id" class="error-message">
                {{ errors.subscription_option_id[0] }}
              </small>
            </div>

            <!-- Start Date -->
            <div class="form-group mb-4">
              <label for="start_date" class="form-label">
                <i class="bi bi-calendar-event"></i>
                Start Date <span class="required">*</span>
              </label>
              <DatePicker
                id="start_date"
                v-model="formData.start_date"
                dateFormat="yy-mm-dd"
                :class="{ 'p-invalid': errors.start_date }"
                class="w-100"
              />
              <small v-if="errors.start_date" class="error-message">
                {{ errors.start_date[0] }}
              </small>
            </div>
          </div>
        </div>

        <!-- Pricing Section -->
        <div class="form-section" style="animation-delay: 0.2s">
          <div class="section-header">
            <div class="section-icon section-icon-success">
              <i class="bi bi-cash-coin"></i>
            </div>
            <div class="section-text">
              <h3 class="section-title">Pricing & Discounts</h3>
              <p class="section-subtitle">Optional custom pricing and discount settings</p>
            </div>
          </div>

          <div class="section-body">
            <div class="form-row">
              <!-- Custom Price -->
              <div class="form-group">
                <label for="custom_price" class="form-label">
                  <i class="bi bi-tag"></i>
                  Custom Price (Optional)
                </label>
                <InputNumber
                  id="custom_price"
                  v-model="formData.custom_price"
                  mode="currency"
                  currency="USD"
                  locale="en-US"
                  placeholder="Leave empty to use default price"
                  :class="{ 'p-invalid': errors.custom_price }"
                  class="w-100"
                />
                <small v-if="selectedSubscriptionOption && !formData.custom_price" class="hint-text">
                  Default price: ${{ selectedSubscriptionOption.price }}
                </small>
                <small v-if="errors.custom_price" class="error-message">
                  {{ errors.custom_price[0] }}
                </small>
              </div>

              <!-- Discount Type -->
              <div class="form-group">
                <label for="discount_type" class="form-label">
                  <i class="bi bi-percent"></i>
                  Discount Type
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
            </div>

            <div class="form-row">
              <!-- Discount Value -->
              <div class="form-group">
                <label for="discount_value" class="form-label">
                  <i class="bi bi-calculator"></i>
                  Discount Value
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

              <!-- Coupon Code -->
              <div class="form-group">
                <label for="coupon_code" class="form-label">
                  <i class="bi bi-ticket-perforated"></i>
                  Coupon Code
                </label>
                <InputText
                  id="coupon_code"
                  v-model="formData.coupon_code"
                  placeholder="Enter coupon code (optional)"
                  :class="{ 'p-invalid': errors.coupon_code }"
                  class="w-100"
                />
                <small v-if="errors.coupon_code" class="error-message">
                  {{ errors.coupon_code[0] }}
                </small>
              </div>
            </div>

            <!-- Price Summary -->
            <div v-if="selectedSubscriptionOption" class="price-summary">
              <div class="price-row">
                <span class="price-label">Base Price:</span>
                <span class="price-value">${{ formData.custom_price || selectedSubscriptionOption.price }}</span>
              </div>
              <div v-if="formData.discount_value && formData.discount_type" class="price-row discount-row">
                <span class="price-label">
                  Discount ({{ formData.discount_type === 'percent' ? `${formData.discount_value}%` : `$${formData.discount_value}` }}):
                </span>
                <span class="price-value">
                  -${{ ((formData.custom_price || selectedSubscriptionOption.price) - effectivePrice).toFixed(2) }}
                </span>
              </div>
              <div class="price-row total-row">
                <span class="price-label">Final Price:</span>
                <span class="price-value final-price">${{ effectivePrice.toFixed(2) }}</span>
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
            :label="submitting ? 'Creating...' : 'Create Subscription'"
            icon="bi bi-check-circle"
            :loading="submitting"
            :disabled="submitting"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped src="../../../../css/subform.css"></style>
