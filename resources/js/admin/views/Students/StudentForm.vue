<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import Password from 'primevue/password';

const router = useRouter();
const route = useRoute();

const isEditMode = computed(() => !!route.params.id);
const studentId = computed(() => route.params.id);

const loading = ref(false);
const programs = ref([]);
const subscriptionOptions = ref([]);

const formData = ref({
  admission_number: '',
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  password: '',
  birthdate: null,
  gender: null,
  academic_year: '',
  program_id: null,
  class_section: '',
  address: '',
  guardian_name: '',
  status: 'active'
});

const subscriptionData = ref({
  enabled: false,
  program_id: null,
  subscription_option_id: null,
  custom_price: '',
  discount_type: null,
  discount_value: '',
  coupon_code: '',
  start_date: new Date()
});

// Coupon verification state
const couponVerified = ref(false);
const verifyingCoupon = ref(false);
const couponDiscount = ref(null);

const errors = ref({});

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending Payment', value: 'pending_payment' },
  { label: 'Expired', value: 'expired' },
  { label: 'Archived', value: 'archived' }
];

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' }
];

const discountTypeOptions = [
  { label: 'No Discount', value: null },
  { label: 'Percentage', value: 'percent' },
  { label: 'Fixed Amount', value: 'fixed' }
];

const fetchStudent = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  try {
    const response = await axios.get(`/admin/students/${studentId.value}`);
    const student = response.data.data;

    formData.value = {
      admission_number: student.admission_number,
      first_name: student.first_name,
      last_name: student.last_name,
      phone: student.phone,
      email: student.email || '',
      password: '',
      birthdate: student.birthdate ? new Date(student.birthdate) : null,
      gender: student.gender || null,
      academic_year: student.academic_year || '',
      program_id: student.program_id || null,
      class_section: student.class_section || '',
      address: student.address || '',
      guardian_name: student.guardian_name || '',
      status: student.status
    };
  } catch (error) {
    console.error('Error fetching student:', error);
    alert('Failed to fetch student details');
  } finally {
    loading.value = false;
  }
};

const fetchPrograms = async () => {
  try {
    const response = await axios.get('/admin/programs');
    programs.value = response.data.data.map(p => ({ label: p.name, value: p.id }));
  } catch (error) {
    console.error('Error fetching programs:', error);
  }
};

const fetchSubscriptionOptions = async () => {
  try {
    const response = await axios.get('/admin/subscription-options');
    subscriptionOptions.value = response.data.data.map(opt => ({
      label: `${opt.name} - $${opt.price} (${opt.duration_months} months)`,
      value: opt.id,
      price: opt.price
    }));
  } catch (error) {
    console.error('Error fetching subscription options:', error);
  }
};

// Computed properties for coupon
const selectedSubscriptionOption = computed(() => {
  if (!subscriptionData.value.subscription_option_id) return null;
  return subscriptionOptions.value.find(opt => opt.value === subscriptionData.value.subscription_option_id);
});

const basePrice = computed(() => {
  return subscriptionData.value.custom_price || selectedSubscriptionOption.value?.price || 0;
});

// Coupon verification functions
const verifyCoupon = async () => {
  if (!subscriptionData.value.coupon_code) {
    alert('Please enter a coupon code');
    return;
  }

  if (!basePrice.value) {
    alert('Please select a subscription option first');
    return;
  }

  verifyingCoupon.value = true;
  errors.value.coupon_code = null;

  try {
    const response = await axios.post('/admin/coupons/verify', {
      code: subscriptionData.value.coupon_code,
      amount: basePrice.value
    });

    if (response.data.success && response.data.available) {
      couponVerified.value = true;
      couponDiscount.value = response.data.data;

      // Clear manual discount when coupon is verified
      subscriptionData.value.discount_type = null;
      subscriptionData.value.discount_value = null;

      alert(`Coupon verified! You'll save $${couponDiscount.value.discount_amount}`);
    }
  } catch (error) {
    couponVerified.value = false;
    couponDiscount.value = null;

    if (error.response?.data?.message) {
      errors.value.coupon_code = [error.response.data.message];
    } else {
      alert('Failed to verify coupon');
    }
  } finally {
    verifyingCoupon.value = false;
  }
};

const removeCoupon = () => {
  subscriptionData.value.coupon_code = '';
  couponVerified.value = false;
  couponDiscount.value = null;
  errors.value.coupon_code = null;
};

const handleSubmit = async () => {
  errors.value = {};
  loading.value = true;

  try {
    const payload = {
      ...formData.value,
      birthdate: formData.value.birthdate ? formData.value.birthdate.toISOString().split('T')[0] : null
    };

    // Remove password if empty in edit mode
    if (isEditMode.value && !payload.password) {
      delete payload.password;
    }

    // Add subscription data if enabled
    if (subscriptionData.value.enabled && !isEditMode.value) {
      payload.subscription = {
        program_id: subscriptionData.value.program_id,
        subscription_option_id: subscriptionData.value.subscription_option_id,
        custom_price: subscriptionData.value.custom_price || null,
        discount_type: subscriptionData.value.discount_type || null,
        discount_value: subscriptionData.value.discount_value || null,
        coupon_code: subscriptionData.value.coupon_code || null,
        start_date: subscriptionData.value.start_date.toISOString().split('T')[0]
      };
    }

    if (isEditMode.value) {
      await axios.put(`/admin/students/${studentId.value}`, payload);
      alert('Student updated successfully');
    } else {
      await axios.post('/admin/students', payload);
      alert('Student created successfully');
    }

    router.push('/students');
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      alert(error.response?.data?.message || 'Failed to save student');
    }
  } finally {
    loading.value = false;
  }
};

const handleProgramChange = () => {
  if (subscriptionData.value.enabled) {
    subscriptionData.value.program_id = formData.value.program_id;
  }
};

onMounted(() => {
  fetchPrograms();
  fetchSubscriptionOptions();
  fetchStudent();
});
</script>

<template>
  <div class="student-form">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-wrapper">
        <div class="header-text">
          <h1 class="page-title">
            <i class="bi bi-person-plus-fill me-3"></i>
            {{ isEditMode ? 'Edit Student' : 'Add New Student' }}
          </h1>
          <p class="page-subtitle">
            {{ isEditMode ? 'Update student information and academic details' : 'Register a new student in the system' }}
          </p>
        </div>
        <Button
          label="Back to List"
          icon="bi bi-arrow-right"
          iconPos="right"
          outlined
          severity="secondary"
          @click="router.push('/students')"
          class="back-btn"
        />
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="student-form-content">
      <!-- Personal Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="bi bi-person-circle"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">Personal Information</h3>
            <p class="section-subtitle">Basic student details and contact information</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-hash me-2"></i>
                  Admission Number
                  <span class="required">*</span>
                </label>
                <InputText
                  v-model="formData.admission_number"
                  :disabled="isEditMode"
                  :invalid="!!errors.admission_number"
                  placeholder="Enter admission number"
                  class="w-100"
                />
                <small v-if="errors.admission_number" class="error-message">
                  {{ errors.admission_number[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-toggle-on me-2"></i>
                  Status
                  <span class="required">*</span>
                </label>
                <Select
                  v-model="formData.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select status"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-person me-2"></i>
                  First Name
                  <span class="required">*</span>
                </label>
                <InputText
                  v-model="formData.first_name"
                  :invalid="!!errors.first_name"
                  placeholder="Enter first name"
                  class="w-100"
                />
                <small v-if="errors.first_name" class="error-message">
                  {{ errors.first_name[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-person me-2"></i>
                  Last Name
                  <span class="required">*</span>
                </label>
                <InputText
                  v-model="formData.last_name"
                  :invalid="!!errors.last_name"
                  placeholder="Enter last name"
                  class="w-100"
                />
                <small v-if="errors.last_name" class="error-message">
                  {{ errors.last_name[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-telephone me-2"></i>
                  Phone Number
                  <span class="required">*</span>
                </label>
                <InputText
                  v-model="formData.phone"
                  :invalid="!!errors.phone"
                  placeholder="Enter phone number"
                  class="w-100"
                />
                <small v-if="errors.phone" class="error-message">
                  {{ errors.phone[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-envelope me-2"></i>
                  Email Address
                </label>
                <InputText
                  v-model="formData.email"
                  type="email"
                  :invalid="!!errors.email"
                  placeholder="Enter email address"
                  class="w-100"
                />
                <small v-if="errors.email" class="error-message">
                  {{ errors.email[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-key me-2"></i>
                  Password
                  <span v-if="!isEditMode" class="required">*</span>
                </label>
                <Password
                  v-model="formData.password"
                  :invalid="!!errors.password"
                  :feedback="false"
                  placeholder="Enter password"
                  toggleMask
                  class="w-100"
                  inputClass="w-100"
                />
                <small v-if="isEditMode" class="field-hint">
                  Leave blank to keep current password
                </small>
                <small v-if="errors.password" class="error-message">
                  {{ errors.password[0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-calendar me-2"></i>
                  Birthdate
                </label>
                <DatePicker
                  v-model="formData.birthdate"
                  dateFormat="yy-mm-dd"
                  placeholder="Select birthdate"
                  showIcon
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-gender-ambiguous me-2"></i>
                  Gender
                </label>
                <Select
                  v-model="formData.gender"
                  :options="genderOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select gender"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-people me-2"></i>
                  Guardian Name
                </label>
                <InputText
                  v-model="formData.guardian_name"
                  placeholder="Enter guardian name"
                  class="w-100"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Academic Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-success">
            <i class="bi bi-mortarboard"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">Academic Information</h3>
            <p class="section-subtitle">Program enrollment and academic details</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-book me-2"></i>
                  Program
                </label>
                <Select
                  v-model="formData.program_id"
                  :options="programs"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select program"
                  @change="handleProgramChange"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-calendar-range me-2"></i>
                  Academic Year
                </label>
                <InputText
                  v-model="formData.academic_year"
                  placeholder="e.g., 2024-2025"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-diagram-3 me-2"></i>
                  Class Section
                </label>
                <InputText
                  v-model="formData.class_section"
                  placeholder="e.g., A, B, C"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-12">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-geo-alt me-2"></i>
                  Address
                </label>
                <Textarea
                  v-model="formData.address"
                  rows="3"
                  placeholder="Enter full address"
                  class="w-100"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Subscription (Only for new students) -->
      <div v-if="!isEditMode" class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-warning">
            <i class="bi bi-credit-card"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">Initial Subscription</h3>
            <p class="section-subtitle">Optional subscription setup for new student</p>
          </div>
          <div class="section-toggle">
            <ToggleSwitch v-model="subscriptionData.enabled" />
          </div>
        </div>
        <div v-if="subscriptionData.enabled" class="section-body">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-book me-2"></i>
                  Program
                  <span class="required">*</span>
                </label>
                <Select
                  v-model="subscriptionData.program_id"
                  :options="programs"
                  optionLabel="label"
                  optionValue="value"
                  :invalid="!!errors['subscription.program_id']"
                  placeholder="Select program"
                  class="w-100"
                />
                <small v-if="errors['subscription.program_id']" class="error-message">
                  {{ errors['subscription.program_id'][0] }}
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-tag me-2"></i>
                  Subscription Option
                  <span class="required">*</span>
                </label>
                <Select
                  v-model="subscriptionData.subscription_option_id"
                  :options="subscriptionOptions"
                  optionLabel="label"
                  optionValue="value"
                  :invalid="!!errors['subscription.subscription_option_id']"
                  placeholder="Select option"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-calendar-check me-2"></i>
                  Start Date
                  <span class="required">*</span>
                </label>
                <DatePicker
                  v-model="subscriptionData.start_date"
                  dateFormat="yy-mm-dd"
                  placeholder="Select start date"
                  showIcon
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-cash me-2"></i>
                  Custom Price
                </label>
                <InputText
                  v-model="subscriptionData.custom_price"
                  type="number"
                  step="0.01"
                  placeholder="Leave blank for default price"
                  class="w-100"
                />
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-percent me-2"></i>
                  Discount Type
                </label>
                <Select
                  v-model="subscriptionData.discount_type"
                  :options="discountTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  :disabled="couponVerified"
                  placeholder="Select type"
                  class="w-100"
                />
                <small v-if="couponVerified" class="field-hint">
                  Disabled when using coupon
                </small>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-dash-circle me-2"></i>
                  Discount Value
                </label>
                <InputText
                  v-model="subscriptionData.discount_value"
                  type="number"
                  step="0.01"
                  :disabled="!subscriptionData.discount_type || couponVerified"
                  placeholder="Enter value"
                  class="w-100"
                />
                <small v-if="couponVerified" class="field-hint">
                  Disabled when using coupon
                </small>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-ticket-perforated me-2"></i>
                  Coupon Code
                </label>
                <div class="coupon-input-group">
                  <InputText
                    v-model="subscriptionData.coupon_code"
                    :invalid="!!errors.coupon_code"
                    :disabled="couponVerified"
                    placeholder="Enter coupon code"
                    class="coupon-input"
                  />
                  <Button
                    v-if="!couponVerified"
                    label="Verify"
                    icon="bi bi-check-circle"
                    :loading="verifyingCoupon"
                    @click="verifyCoupon"
                    severity="success"
                    type="button"
                    class="verify-btn"
                  />
                  <Button
                    v-else
                    label="Remove"
                    icon="bi bi-x-circle"
                    @click="removeCoupon"
                    severity="danger"
                    type="button"
                    class="remove-btn"
                  />
                </div>
                <small v-if="errors.coupon_code" class="error-message">
                  {{ errors.coupon_code[0] }}
                </small>
                <small v-if="couponVerified" class="success-message">
                  <i class="bi bi-check-circle-fill me-1"></i>
                  Coupon verified successfully!
                </small>
              </div>
            </div>

            <!-- Coupon Discount Preview -->
            <div v-if="couponVerified && couponDiscount" class="col-12">
              <div class="discount-preview">
                <div class="preview-header">
                  <i class="bi bi-receipt-cutoff"></i>
                  <span>Coupon Discount Applied</span>
                </div>
                <div class="preview-body">
                  <div class="preview-row">
                    <span class="preview-label">Original Amount:</span>
                    <span class="preview-value">${{ couponDiscount.original_amount }}</span>
                  </div>
                  <div class="preview-row discount-row">
                    <span class="preview-label">
                      Discount ({{ couponDiscount.discount_type === 'percent' ? `${couponDiscount.discount_value}%` : 'Fixed' }}):
                    </span>
                    <span class="preview-value">-${{ couponDiscount.discount_amount }}</span>
                  </div>
                  <div class="preview-row final-row">
                    <span class="preview-label">Final Amount:</span>
                    <span class="preview-value">${{ couponDiscount.final_amount }}</span>
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
          @click="router.push('/students')"
          type="button"
        />
        <Button
          :label="isEditMode ? 'Update Student' : 'Create Student'"
          :icon="loading ? 'pi pi-spin pi-spinner' : 'bi bi-check-lg'"
          :loading="loading"
          type="submit"
          class="submit-btn"
        />
      </div>
    </form>
  </div>
</template>
