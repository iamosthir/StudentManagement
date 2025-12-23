<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';

const router = useRouter();
const route = useRoute();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const loadingOptions = ref(false);
const loadingProducts = ref(false);
const subscriptionOptions = ref([]);
const products = ref([]);
const errors = ref({});

const form = ref({
  name: '',
  description: '',
  is_active: true,
  subscription_option_ids: [],
  product_ids: []
});

const loadSubscriptionOptions = async () => {
  loadingOptions.value = true;
  try {
    const response = await axios.get('/admin/programs/subscription-options');
    subscriptionOptions.value = response.data.data.map(opt => ({
      label: `${opt.name} - $${opt.price} (${opt.duration_months} months)`,
      value: opt.id
    }));
  } catch (error) {
    console.error('Error loading subscription options:', error);
  } finally {
    loadingOptions.value = false;
  }
};

const loadProducts = async () => {
  loadingProducts.value = true;
  try {
    const response = await axios.get('/admin/programs/products');
    products.value = response.data.data.map(p => ({
      label: `${p.name} - $${p.price} (${p.type})`,
      value: p.id
    }));
  } catch (error) {
    console.error('Error loading products:', error);
  } finally {
    loadingProducts.value = false;
  }
};

const loadProgram = async () => {
  try {
    const response = await axios.get(`/admin/programs/${route.params.id}`);
    const program = response.data.data;

    form.value = {
      name: program.name,
      description: program.description || '',
      is_active: program.is_active,
      subscription_option_ids: program.subscription_options?.map(o => o.id) || [],
      product_ids: program.products?.map(p => p.id) || []
    };
  } catch (error) {
    console.error('Error loading program:', error);
    alert('فشل في تحميل البرنامج');
    router.push('/programs');
  }
};

const submitForm = async () => {
  errors.value = {};
  loading.value = true;

  try {
    const url = isEdit.value
      ? `/admin/programs/${route.params.id}`
      : '/admin/programs';

    const method = isEdit.value ? 'put' : 'post';

    await axios[method](url, form.value);

    router.push('/programs');
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    } else {
      console.error('Error submitting form:', error);
      alert('فشل في حفظ البرنامج');
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadSubscriptionOptions();
  loadProducts();

  if (isEdit.value) {
    loadProgram();
  }
});
</script>

<template>
  <div class="program-form" dir="rtl">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-wrapper">
        <div class="header-text">
          <h1 class="page-title">
            <i class="bi bi-book-fill me-3"></i>
            {{ isEdit ? 'تعديل برنامج' : 'إضافة برنامج جديد' }}
          </h1>
          <p class="page-subtitle">
            {{ isEdit ? 'تحديث تفاصيل البرنامج والمهام' : 'إنشاء برنامج تعليمي جديد' }}
          </p>
        </div>
        <Button
          label="العودة للقائمة"
          icon="bi bi-arrow-right"
          iconPos="right"
          outlined
          severity="secondary"
          @click="router.push('/programs')"
          class="back-btn"
        />
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submitForm" class="program-form-content">
      <!-- Basic Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="bi bi-info-circle"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">المعلومات الأساسية</h3>
            <p class="section-subtitle">اسم البرنامج والوصف والحالة</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-md-8">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-journal-text me-2"></i>
                  اسم البرنامج
                  <span class="required">*</span>
                </label>
                <InputText
                  v-model="form.name"
                  :invalid="!!errors.name"
                  placeholder="أدخل اسم البرنامج"
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
                  الحالة
                </label>
                <div class="toggle-field">
                  <ToggleSwitch v-model="form.is_active" />
                  <span class="toggle-label">{{ form.is_active ? 'نشط' : 'غير نشط' }}</span>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-text-paragraph me-2"></i>
                  الوصف
                </label>
                <Textarea
                  v-model="form.description"
                  :invalid="!!errors.description"
                  rows="4"
                  placeholder="أدخل وصف البرنامج"
                  class="w-100"
                />
                <small v-if="errors.description" class="error-message">
                  {{ errors.description[0] }}
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Subscription Options -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-success">
            <i class="bi bi-bookmark"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">خيارات الاشتراك</h3>
            <p class="section-subtitle">تعيين خطط الاشتراك لهذا البرنامج</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-12">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-tags me-2"></i>
                  اختيار خيارات الاشتراك
                </label>
                <MultiSelect
                  v-model="form.subscription_option_ids"
                  :options="subscriptionOptions"
                  optionLabel="label"
                  optionValue="value"
                  :loading="loadingOptions"
                  placeholder="اختر خيارات الاشتراك"
                  class="w-100"
                  display="chip"
                />
                <small class="field-hint">
                  يمكن للطلاب الاشتراك في هذا البرنامج باستخدام الخيارات المحددة
                </small>
                <small v-if="errors.subscription_option_ids" class="error-message">
                  {{ errors.subscription_option_ids[0] }}
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon section-icon-warning">
            <i class="bi bi-box"></i>
          </div>
          <div class="section-title-wrapper">
            <h3 class="section-title">المنتجات</h3>
            <p class="section-subtitle">تعيين المنتجات والمواد لهذا البرنامج</p>
          </div>
        </div>
        <div class="section-body">
          <div class="row g-4">
            <div class="col-12">
              <div class="form-field">
                <label class="field-label">
                  <i class="bi bi-box-seam me-2"></i>
                  اختيار المنتجات
                </label>
                <MultiSelect
                  v-model="form.product_ids"
                  :options="products"
                  optionLabel="label"
                  optionValue="value"
                  :loading="loadingProducts"
                  placeholder="اختر المنتجات"
                  class="w-100"
                  display="chip"
                />
                <small class="field-hint">
                  منتجات مثل الكتب والزي والمواد المرتبطة بهذا البرنامج
                </small>
                <small v-if="errors.product_ids" class="error-message">
                  {{ errors.product_ids[0] }}
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Buttons -->
      <div class="form-actions">
        <Button
          label="إلغاء"
          icon="bi bi-x-lg"
          outlined
          severity="secondary"
          @click="router.push('/programs')"
          type="button"
        />
        <Button
          :label="isEdit ? 'تحديث البرنامج' : 'إنشاء البرنامج'"
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
.program-form {
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
  margin-right: 0.25rem;
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
:deep(.p-multiselect),
:deep(.p-inputtextarea) {
  border-radius: 12px !important;
  border: 2px solid #e2e8f0 !important;
  padding: 0.75rem 1rem !important;
  font-size: 0.9375rem !important;
  transition: all 0.3s ease !important;
}

:deep(.p-inputtext:focus),
:deep(.p-select:focus),
:deep(.p-multiselect:focus),
:deep(.p-inputtextarea:focus) {
  border-color: #6366f1 !important;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
}

:deep(.p-inputtext.p-invalid),
:deep(.p-select.p-invalid),
:deep(.p-multiselect.p-invalid) {
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

:deep(.p-multiselect-label) {
  padding: 0.5rem !important;
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

  .form-actions {
    flex-direction: column;
  }

  .form-actions :deep(.p-button) {
    width: 100%;
  }
}
</style>
