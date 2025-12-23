<template>
  <div class="product-form-page" dir="rtl">
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1 class="page-title">{{ isEdit ? 'تعديل منتج' : 'إضافة منتج جديد' }}</h1>
          <p class="page-subtitle">{{ isEdit ? 'تحديث معلومات المنتج' : 'إنشاء منتج جديد' }}</p>
        </div>
        <router-link to="/products" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-2"></i>
          العودة للمنتجات
        </router-link>
      </div>
    </div>

    <form @submit.prevent="handleSubmit">
      <div class="form-sections">
        <!-- Basic Information Section -->
        <div class="form-section" style="animation-delay: 0.1s">
          <div class="section-header">
            <div class="section-icon primary">
              <i class="bi bi-box-seam"></i>
            </div>
            <div>
              <h3 class="section-title">Basic Information</h3>
              <p class="section-subtitle">Enter the product name and status</p>
            </div>
          </div>

          <div class="section-body">
            <div class="row g-4">
              <div class="col-md-8">
                <div class="form-group">
                  <label for="name" class="form-label required">اسم المنتج</label>
                  <InputText
                    id="name"
                    v-model="form.name"
                    :class="['w-100', { 'p-invalid': errors.name }]"
                    placeholder="أدخل اسم المنتج"
                  />
                  <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label for="is_active" class="form-label">الحالة</label>
                  <div class="toggle-wrapper">
                    <ToggleSwitch v-model="form.is_active" input-id="is_active" />
                    <span class="toggle-label">{{ form.is_active ? 'نشط' : 'غير نشط' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pricing & Type Section -->
        <div class="form-section" style="animation-delay: 0.2s">
          <div class="section-header">
            <div class="section-icon success">
              <i class="bi bi-currency-dollar"></i>
            </div>
            <div>
              <h3 class="section-title">السعر والنوع</h3>
              <p class="section-subtitle">تعيين سعر المنتج وتصنيفه</p>
            </div>
          </div>

          <div class="section-body">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="price" class="form-label required">السعر</label>
                  <InputNumber
                    id="price"
                    v-model="form.price"
                    mode="currency"
                    currency="USD"
                    locale="en-US"
                    :minFractionDigits="2"
                    :maxFractionDigits="2"
                    :min="0"
                    :max="999999.99"
                    :class="['w-100', { 'p-invalid': errors.price }]"
                    placeholder="0.00"
                  />
                  <small v-if="errors.price" class="p-error">{{ errors.price }}</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="type" class="form-label required">نوع المنتج</label>
                  <Select
                    id="type"
                    v-model="form.type"
                    :options="productTypes"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="اختر نوع المنتج"
                    :class="['w-100', { 'p-invalid': errors.type }]"
                  />
                  <small v-if="errors.type" class="p-error">{{ errors.type }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Card -->
        <div v-if="form.name || form.price" class="summary-card" style="animation-delay: 0.3s">
          <div class="summary-header">
            <div class="summary-icon">
              <i class="bi bi-card-checklist"></i>
            </div>
            <h4>ملخص المنتج</h4>
          </div>
          <div class="summary-body">
            <div class="summary-row">
              <span class="summary-label">اسم المنتج:</span>
              <span class="summary-value">{{ form.name || '-' }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">السعر:</span>
              <span class="summary-value price">
                {{ form.price ? `$${parseFloat(form.price).toFixed(2)}` : '-' }}
              </span>
            </div>
            <div class="summary-row">
              <span class="summary-label">النوع:</span>
              <span class="summary-value">
                {{ productTypes.find(t => t.value === form.type)?.label || '-' }}
              </span>
            </div>
            <div class="summary-row">
              <span class="summary-label">الحالة:</span>
              <span :class="['summary-value', form.is_active ? 'status-active' : 'status-inactive']">
                {{ form.is_active ? 'نشط' : 'غير نشط' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="form-actions">
        <Button
          type="submit"
          :label="isEdit ? 'تحديث المنتج' : 'إنشاء المنتج'"
          :loading="loading"
          :icon="isEdit ? 'bi bi-check-circle' : 'bi bi-plus-circle'"
          class="btn-submit"
        />
        <Button
          type="button"
          label="إلغاء"
          severity="secondary"
          outlined
          @click="$router.push('/products')"
          :disabled="loading"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const loading = ref(false);
const productTypes = ref([]);
const errors = ref({});

const form = ref({
  name: '',
  price: null,
  type: '',
  is_active: true,
});

const isEdit = computed(() => !!route.params.id);

const loadProductTypes = async () => {
  try {
    const response = await axios.get('/admin/products/types');
    productTypes.value = response.data.data;
  } catch (error) {
    console.error('Error loading product types:', error);
  }
};

const loadProduct = async () => {
  if (!isEdit.value) return;

  loading.value = true;
  try {
    const response = await axios.get(`/admin/products/${route.params.id}`);
    const product = response.data.data;

    form.value = {
      name: product.name,
      price: parseFloat(product.price),
      type: product.type,
      is_active: product.is_active,
    };
  } catch (error) {
    console.error('Error loading product:', error);
    toast.add({
      severity: 'error',
      summary: 'خطأ',
      detail: 'خطأ في تحميل المنتج',
      life: 3000
    });
    router.push('/products');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  loading.value = true;

  try {
    const url = isEdit.value
      ? `/admin/products/${route.params.id}`
      : '/admin/products';

    const method = isEdit.value ? 'put' : 'post';

    await axios[method](url, form.value);

    router.push('/products');
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.data?.message) {
      toast.add({
        severity: 'error',
        summary: 'خطأ',
        detail: error.response.data.message,
        life: 3000
      });
    } else {
      toast.add({
        severity: 'error',
        summary: 'خطأ',
        detail: 'خطأ في حفظ المنتج',
        life: 3000
      });
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadProductTypes();
  loadProduct();
});
</script>

<style scoped>
.product-form-page {
  padding: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  animation: fadeIn 0.6s ease-out;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
}

.page-subtitle {
  color: #64748b;
  margin: 0.25rem 0 0 0;
  font-size: 0.95rem;
}

.form-sections {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.08);
  animation: fadeInUp 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) backwards;
  transition: all 0.3s ease;
}

.form-section:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(99, 102, 241, 0.12);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.section-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  animation: float 3s ease-in-out infinite;
}

.section-icon.primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.section-icon.success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.section-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0 0;
}

.section-body {
  padding: 0;
}

.form-group {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.form-label.required::after {
  content: '*';
  color: #ef4444;
  margin-right: 0.25rem;
}

.toggle-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0;
}

.toggle-label {
  font-weight: 600;
  color: #1e293b;
}

.summary-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  animation: fadeInUp 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) backwards;
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #cbd5e1;
}

.summary-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
}

.summary-header h4 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
}

.summary-body {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.summary-label {
  font-weight: 500;
  color: #64748b;
  font-size: 0.9rem;
}

.summary-value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

.summary-value.price {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 1.125rem;
}

.summary-value.status-active {
  color: #10b981;
}

.summary-value.status-inactive {
  color: #94a3b8;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px solid #f1f5f9;
  animation: fadeIn 0.6s ease-out 0.4s backwards;
}

.btn-submit {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
  border: none;
  font-weight: 600;
  padding: 0.75rem 2rem;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
}

.p-error {
  display: block;
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.375rem;
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

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

@media (max-width: 768px) {
  .product-form-page {
    padding: 1rem;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .form-section {
    padding: 1.5rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions button {
    width: 100%;
  }
}
</style>
