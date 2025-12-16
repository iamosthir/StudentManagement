<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import AutoComplete from 'primevue/autocomplete';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const students = ref([]);
const selectedStudent = ref(null);
const subscriptions = ref([]);
const products = ref([]);

const formData = ref({
  student_id: null,
  payment_method: 'cash',
  note: '',
  status: 'paid'
});

const items = ref([
  {
    item_type: 'subscription',
    item_id: '',
    description: '',
    quantity: 1,
    unit_price: 0,
    discount_value: 0,
    total_price: 0,
    payment_info: null,
    loading_info: false
  }
]);

const errors = ref({});

const paymentMethods = ref([
  { label: 'Cash', value: 'cash' },
  { label: 'Credit Card', value: 'credit_card' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
  { label: 'Check', value: 'check' },
  { label: 'Online Payment', value: 'online' }
]);

const paymentStatuses = ref([
  { label: 'Paid', value: 'paid' },
  { label: 'Pending', value: 'pending' }
]);

const itemTypes = ref([
  { label: 'Subscription', value: 'subscription' },
  { label: 'Product', value: 'product' }
]);

const totalAmount = computed(() => {
  return items.value.reduce((sum, item) => sum + parseFloat(item.total_price || 0), 0);
});

const subscriptionOptions = computed(() => {
  return subscriptions.value.map(sub => ({
    label: `${sub.subscription_option?.name} - ${sub.program?.name}`,
    value: sub.id
  }));
});

const productOptions = computed(() => {
  return products.value.map(product => ({
    label: product.name,
    value: product.id
  }));
});

// Search students with debouncing
const searchStudents = async (event) => {
  const query = event.query || '';

  if (query.length < 2) {
    students.value = [];
    return;
  }

  try {
    const response = await axios.get('/admin/students', {
      params: { search: query, per_page: 20 }
    });
    students.value = response.data.data.map(student => ({
      id: student.id,
      full_name: student.full_name,
      admission_number: student.admission_number,
      label: `${student.full_name} - ${student.admission_number}`
    }));
  } catch (error) {
    console.error('Error searching students:', error);
  }
};

// Fetch a specific student by ID (for query param)
const fetchStudentById = async (studentId) => {
  try {
    const response = await axios.get(`/admin/students/${studentId}`);
    const student = response.data.data;
    selectedStudent.value = {
      id: student.id,
      full_name: student.full_name,
      admission_number: student.admission_number,
      label: `${student.full_name} - ${student.admission_number}`
    };
    formData.value.student_id = student.id;
    await fetchStudentSubscriptions(student.id);
  } catch (error) {
    console.error('Error fetching student:', error);
    alert('Failed to load student information');
  }
};

const fetchStudentSubscriptions = async (studentId) => {
  if (!studentId) {
    subscriptions.value = [];
    return;
  }

  try {
    const response = await axios.get('/admin/subscriptions', {
      params: { student_id: studentId, per_page: 1000 }
    });
    subscriptions.value = response.data.data;
  } catch (error) {
    console.error('Error fetching subscriptions:', error);
  }
};

const fetchProducts = async () => {
  try {
    const response = await axios.get('/admin/products', { params: { per_page: 1000 } });
    products.value = response.data.data;
  } catch (error) {
    console.error('Error fetching products:', error);
  }
};

// Fetch payment info for an item
const fetchPaymentInfo = async (item) => {
  if (!formData.value.student_id || !item.item_id || !item.item_type) {
    return;
  }

  item.loading_info = true;

  try {
    const response = await axios.post('/admin/payments/item-payment-info', {
      student_id: formData.value.student_id,
      item_type: item.item_type,
      item_id: item.item_id
    });

    item.payment_info = response.data.data;

    // If full payment required, set total_price to remaining amount
    if (item.payment_info.is_full_payment) {
      item.total_price = item.payment_info.remaining_amount;
    }
  } catch (error) {
    console.error('Error fetching payment info:', error);
    item.payment_info = null;
  } finally {
    item.loading_info = false;
  }
};

const addItem = () => {
  items.value.push({
    item_type: 'subscription',
    item_id: '',
    description: '',
    quantity: 1,
    unit_price: 0,
    discount_value: 0,
    total_price: 0,
    payment_info: null,
    loading_info: false
  });
};

const removeItem = (index) => {
  if (items.value.length > 1) {
    items.value.splice(index, 1);
  }
};

const calculateItemTotal = (item) => {
  const subtotal = parseFloat(item.unit_price || 0) * parseInt(item.quantity || 1);
  const discount = parseFloat(item.discount_value || 0);
  let calculatedTotal = (subtotal - discount);

  // Validate against payment info if available
  if (item.payment_info) {
    if (item.payment_info.is_full_payment) {
      // Must be exact remaining amount
      calculatedTotal = item.payment_info.remaining_amount;
    } else {
      // Can't exceed remaining amount
      calculatedTotal = Math.min(calculatedTotal, item.payment_info.remaining_amount);
    }
  }

  item.total_price = Math.max(0, calculatedTotal).toFixed(2);
};

const handleItemTypeChange = (item) => {
  item.item_id = '';
  item.description = '';
  item.unit_price = 0;
  item.discount_value = 0;
  item.payment_info = null;
  calculateItemTotal(item);
};

const handleItemSelect = async (item) => {
  if (item.item_type === 'subscription') {
    const subscription = subscriptions.value.find(s => s.id == item.item_id);
    if (subscription) {
      item.description = `${subscription.subscription_option?.name || 'Subscription'} - ${subscription.program?.name || ''}`;
      item.unit_price = subscription.final_price;
      item.quantity = 1;
      await fetchPaymentInfo(item);
      calculateItemTotal(item);
    }
  } else if (item.item_type === 'product') {
    const product = products.value.find(p => p.id == item.item_id);
    if (product) {
      item.description = product.name;
      item.unit_price = product.price;
      await fetchPaymentInfo(item);
      calculateItemTotal(item);
    }
  }
};

const handleStudentSelect = (event) => {
  if (event.value) {
    formData.value.student_id = event.value.id;
    fetchStudentSubscriptions(event.value.id);

    // Reset items payment info when student changes
    items.value.forEach(item => {
      item.payment_info = null;
      if (item.item_id) {
        fetchPaymentInfo(item);
      }
    });
  } else {
    formData.value.student_id = null;
    subscriptions.value = [];
  }
};

const validateTotalPrice = (item) => {
  if (!item.payment_info) return true;

  const totalPrice = parseFloat(item.total_price || 0);

  if (item.payment_info.is_full_payment) {
    // Must be exact remaining amount
    if (totalPrice !== parseFloat(item.payment_info.remaining_amount)) {
      return false;
    }
  } else {
    // Must be between min and max
    if (totalPrice < item.payment_info.min_payment || totalPrice > item.payment_info.max_payment) {
      return false;
    }
  }

  return true;
};

const handleSubmit = async () => {
  errors.value = {};

  // Validate payment amounts
  let hasErrors = false;
  items.value.forEach((item, index) => {
    if (!validateTotalPrice(item)) {
      if (item.payment_info?.is_full_payment) {
        errors.value[`items.${index}.total_price`] = [`Full payment required: $${item.payment_info.remaining_amount}`];
      } else {
        errors.value[`items.${index}.total_price`] = [`Amount must be between $${item.payment_info?.min_payment} and $${item.payment_info?.max_payment}`];
      }
      hasErrors = true;
    }
  });

  if (hasErrors) {
    alert('Please fix the payment amount errors before submitting');
    return;
  }

  loading.value = true;

  try {
    const payload = {
      ...formData.value,
      items: items.value.map(item => ({
        item_type: item.item_type,
        item_id: parseInt(item.item_id),
        description: item.description,
        quantity: parseInt(item.quantity),
        unit_price: parseFloat(item.unit_price),
        discount_value: parseFloat(item.discount_value || 0),
        total_price: parseFloat(item.total_price)
      }))
    };

    await axios.post('/admin/payments', payload);
    alert('Payment created successfully');
    router.push('/payments');
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      alert(error.response?.data?.message || 'Failed to create payment');
    }
  } finally {
    loading.value = false;
  }
};

watch(items, (newItems) => {
  newItems.forEach(item => {
    watch([() => item.unit_price, () => item.quantity, () => item.discount_value], () => {
      calculateItemTotal(item);
    });
  });
}, { deep: true, immediate: true });

onMounted(async () => {
  await fetchProducts();

  // Handle student_id query parameter
  if (route.query.student_id) {
    await fetchStudentById(route.query.student_id);
  }
});
</script>

<template>
  <div class="payment-form-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div class="header-text">
          <h1 class="page-title">Add New Payment</h1>
          <p class="page-subtitle">Record a student payment transaction</p>
        </div>
      </div>
      <div class="header-actions">
        <Button
          label="Back to List"
          icon="bi bi-arrow-left"
          severity="secondary"
          outlined
          @click="router.push('/payments')"
        />
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="payment-form">
      <!-- Payment Information Section -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-primary">
            <i class="bi bi-info-circle-fill"></i>
          </div>
          <div class="section-text">
            <h2 class="section-title">Payment Information</h2>
            <p class="section-subtitle">Basic payment details and transaction method</p>
          </div>
        </div>

        <div class="section-body">
          <div class="form-grid">
            <!-- Student Search -->
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-person"></i>
                Student
                <span class="required">*</span>
              </label>
              <AutoComplete
                v-model="selectedStudent"
                :suggestions="students"
                @complete="searchStudents"
                @item-select="handleStudentSelect"
                optionLabel="label"
                placeholder="Search by name, phone, or admission number..."
                :class="{ 'p-invalid': errors.student_id }"
                forceSelection
                dropdown
              />
              <small v-if="errors.student_id" class="error-message">
                {{ errors.student_id[0] }}
              </small>
              <small v-else class="form-hint">Type at least 2 characters to search</small>
            </div>

            <!-- Payment Method -->
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-credit-card"></i>
                Payment Method
              </label>
              <Select
                v-model="formData.payment_method"
                :options="paymentMethods"
                optionLabel="label"
                optionValue="value"
                placeholder="Select Payment Method"
              />
            </div>

            <!-- Status -->
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-check-circle"></i>
                Status
                <span class="required">*</span>
              </label>
              <Select
                v-model="formData.status"
                :options="paymentStatuses"
                optionLabel="label"
                optionValue="value"
                placeholder="Select Status"
              />
            </div>

            <!-- Note -->
            <div class="form-group form-group-full">
              <label class="form-label">
                <i class="bi bi-pencil-square"></i>
                Note
              </label>
              <Textarea
                v-model="formData.note"
                rows="3"
                placeholder="Optional payment notes..."
                autoResize
              />
              <small class="form-hint">Add any additional notes about this payment</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Items Section -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-success">
            <i class="bi bi-cart-check-fill"></i>
          </div>
          <div class="section-text">
            <h2 class="section-title">Payment Items</h2>
            <p class="section-subtitle">Add subscriptions or products to this payment</p>
          </div>
          <Button
            label="Add Item"
            icon="bi bi-plus-circle"
            severity="success"
            @click="addItem"
            type="button"
            class="btn-add-item"
          />
        </div>

        <div class="section-body">
          <!-- Items List -->
          <div class="items-list">
            <div v-for="(item, index) in items" :key="index" class="payment-item">
              <div class="item-header">
                <div class="item-badge">
                  <i class="bi bi-tag-fill"></i>
                  Item {{ index + 1 }}
                </div>
                <Button
                  v-if="items.length > 1"
                  icon="bi bi-trash"
                  severity="danger"
                  text
                  rounded
                  @click="removeItem(index)"
                  type="button"
                  class="btn-remove"
                />
              </div>

              <!-- Payment Info Card -->
              <div v-if="item.payment_info" class="payment-info-card">
                <div class="info-row">
                  <span class="info-label">Total Price:</span>
                  <span class="info-value">${{ item.payment_info.total_price.toFixed(2) }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Already Paid:</span>
                  <span class="info-value success">${{ item.payment_info.paid_amount.toFixed(2) }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Remaining:</span>
                  <span class="info-value warning">${{ item.payment_info.remaining_amount.toFixed(2) }}</span>
                </div>
                <div v-if="item.payment_info.is_full_payment" class="info-row highlight">
                  <i class="bi bi-exclamation-circle"></i>
                  <span class="info-text">Full payment required: ${{ item.payment_info.remaining_amount.toFixed(2) }}</span>
                </div>
                <div v-else class="info-row highlight">
                  <i class="bi bi-info-circle"></i>
                  <span class="info-text">Partial payment allowed: $1 - ${{ item.payment_info.remaining_amount.toFixed(2) }}</span>
                </div>
              </div>

              <div v-if="item.loading_info" class="loading-info">
                <i class="bi bi-hourglass-split"></i>
                <span>Loading payment information...</span>
              </div>

              <div class="item-content">
                <div class="form-grid">
                  <!-- Item Type -->
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-tag"></i>
                      Item Type
                      <span class="required">*</span>
                    </label>
                    <Select
                      v-model="item.item_type"
                      :options="itemTypes"
                      optionLabel="label"
                      optionValue="value"
                      @change="handleItemTypeChange(item)"
                    />
                  </div>

                  <!-- Item Selection -->
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-box"></i>
                      {{ item.item_type === 'subscription' ? 'Subscription' : 'Product' }}
                      <span class="required">*</span>
                    </label>
                    <Select
                      v-model="item.item_id"
                      :options="item.item_type === 'subscription' ? subscriptionOptions : productOptions"
                      optionLabel="label"
                      optionValue="value"
                      :placeholder="`Select ${item.item_type === 'subscription' ? 'Subscription' : 'Product'}`"
                      :disabled="!formData.student_id && item.item_type === 'subscription'"
                      @change="handleItemSelect(item)"
                      filter
                      showClear
                    />
                  </div>

                  <!-- Description -->
                  <div class="form-group form-group-full">
                    <label class="form-label">
                      <i class="bi bi-text-left"></i>
                      Description
                      <span class="required">*</span>
                    </label>
                    <InputText
                      v-model="item.description"
                      placeholder="Item description"
                    />
                  </div>

                  <!-- Quantity -->
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-hash"></i>
                      Quantity
                      <span class="required">*</span>
                    </label>
                    <InputNumber
                      v-model="item.quantity"
                      :min="1"
                      :max="1000"
                      showButtons
                      buttonLayout="horizontal"
                      decrementButtonClass="p-button-secondary"
                      incrementButtonClass="p-button-secondary"
                      incrementButtonIcon="bi bi-plus"
                      decrementButtonIcon="bi bi-dash"
                    />
                  </div>

                  <!-- Unit Price -->
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-currency-dollar"></i>
                      Unit Price
                      <span class="required">*</span>
                    </label>
                    <InputNumber
                      v-model="item.unit_price"
                      mode="currency"
                      currency="USD"
                      locale="en-US"
                      :min="0"
                      :maxFractionDigits="2"
                      :readonly="item.payment_info?.is_full_payment"
                    />
                  </div>

                  <!-- Discount -->
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-percent"></i>
                      Discount
                    </label>
                    <InputNumber
                      v-model="item.discount_value"
                      mode="currency"
                      currency="USD"
                      locale="en-US"
                      :min="0"
                      :maxFractionDigits="2"
                      placeholder="0.00"
                      :readonly="item.payment_info?.is_full_payment"
                    />
                  </div>

                  <!-- Total Price -->
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-cash"></i>
                      Total Price
                      <span class="required">*</span>
                    </label>
                    <div class="total-price-display" :class="{ 'has-error': errors[`items.${index}.total_price`] }">
                      <span class="currency-symbol">$</span>
                      <span class="price-value">{{ parseFloat(item.total_price || 0).toFixed(2) }}</span>
                    </div>
                    <small v-if="errors[`items.${index}.total_price`]" class="error-message">
                      {{ errors[`items.${index}.total_price`][0] }}
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="payment-summary">
            <div class="summary-content">
              <div class="summary-label">
                <i class="bi bi-calculator"></i>
                Total Amount
              </div>
              <div class="summary-value">
                ${{ totalAmount.toFixed(2) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <Button
          label="Cancel"
          severity="secondary"
          outlined
          @click="router.push('/payments')"
          type="button"
        />
        <Button
          label="Create Payment"
          icon="bi bi-check-circle"
          :loading="loading"
          type="submit"
          :disabled="items.length === 0 || !formData.student_id"
        />
      </div>
    </form>
  </div>
</template>

<style scoped>
.payment-info-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px solid #dee2e6;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #dee2e6;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row.highlight {
  background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
  margin: 8px -16px -16px;
  padding: 12px 16px;
  border-radius: 0 0 10px 10px;
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #495057;
}

.info-value {
  font-weight: 700;
  font-size: 1.1em;
  color: #212529;
}

.info-value.success {
  color: #10b981;
}

.info-value.warning {
  color: #f59e0b;
}

.info-text {
  font-weight: 600;
  color: #856404;
  margin-left: 8px;
}

.loading-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #e3f2fd;
  border-radius: 8px;
  margin-bottom: 16px;
  color: #1976d2;
  font-weight: 500;
}

.loading-info i {
  animation: spin 2s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.total-price-display.has-error {
  border-color: #ef4444;
  background-color: #fee;
}
</style>
