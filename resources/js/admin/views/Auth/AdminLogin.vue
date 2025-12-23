<template>
    <div class="login-wrapper" dir="rtl">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-5 col-lg-4">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <!-- Logo and Title -->
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="bi bi-mortarboard-fill text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <h3 class="fw-bold">إدارة الطلاب</h3>
                                <p class="text-muted">تسجيل دخول لوحة الإدارة</p>
                            </div>

                            <!-- Alert Messages -->
                            <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                {{ errorMessage }}
                                <button type="button" class="btn-close" @click="errorMessage = ''" aria-label="Close"></button>
                            </div>

                            <!-- Login Form -->
                            <form @submit.prevent="handleLogin">
                                <div class="mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <IconField>
                                        <InputIcon class="bi bi-envelope"></InputIcon>
                                        <InputText
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="admin@example.com"
                                            class="w-100"
                                            required
                                            autofocus
                                        />
                                    </IconField>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">كلمة المرور</label>
                                    <IconField>
                                        <InputIcon class="bi bi-lock"></InputIcon>
                                        <Password
                                            id="password"
                                            v-model="form.password"
                                            placeholder="أدخل كلمة المرور"
                                            :toggle-mask="true"
                                            :feedback="false"
                                            class="w-100"
                                            input-class="w-100"
                                            required
                                        />
                                    </IconField>
                                </div>

                                <div class="mb-3 d-flex align-items-center gap-2">
                                    <Checkbox
                                        inputId="remember"
                                        v-model="form.remember"
                                        :binary="true"
                                    />
                                    <label for="remember" class="mb-0" style="cursor: pointer;">
                                        تذكرني
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-lg"
                                        :disabled="loading"
                                    >
                                        <span v-if="loading">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            جاري تسجيل الدخول...
                                        </span>
                                        <span v-else>
                                            <i class="bi bi-box-arrow-in-right me-2"></i>
                                            تسجيل الدخول
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <!-- Additional Links -->
                            <div class="text-center mt-3">
                                <a href="#" class="text-decoration-none text-muted small">
                                    نسيت كلمة المرور؟
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-4">
                        <p class="text-muted small">
                            © {{ currentYear }} نظام إدارة الطلاب
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const router = useRouter();

const form = ref({
    email: '',
    password: '',
    remember: false
});

const loading = ref(false);
const errorMessage = ref('');

const currentYear = computed(() => new Date().getFullYear());

const handleLogin = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post('/admin/login', {
            email: form.value.email,
            password: form.value.password,
            remember: form.value.remember
        });

        if (response.data.success) {
            // Store admin info in localStorage for quick access
            localStorage.setItem('admin_authenticated', 'true');
            localStorage.setItem('admin_user', JSON.stringify(response.data.admin));

            // Redirect to dashboard
            router.push({ name: 'admin.dashboard' });
        }
    } catch (error) {
        if (error.response) {
            // Handle validation errors or authentication errors
            if (error.response.status === 422) {
                errorMessage.value = error.response.data.message || 'بيانات الاعتماد غير صالحة';
            } else if (error.response.status === 403) {
                errorMessage.value = error.response.data.message || 'الحساب معطل';
            } else {
                errorMessage.value = 'فشل تسجيل الدخول. يرجى المحاولة مرة أخرى.';
            }
        } else {
            errorMessage.value = 'خطأ في الشبكة. يرجى فحص اتصالك.';
        }
        console.error('Login error:', error);
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.card {
    border-radius: 1rem;
    backdrop-filter: blur(10px);
}

.card-body {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 1rem;
}

/* PrimeVue Input Styling - Custom theme colors */
:deep(.p-inputtext) {
    padding: 0.75rem;
    padding-right: 2.5rem;
    font-size: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    transition: border-color 0.2s, box-shadow 0.2s;
}

:deep(.p-inputtext:enabled:focus) {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

:deep(.p-password-input) {
    padding: 0.75rem;
    padding-right: 2.5rem;
    font-size: 1rem;
    border-radius: 0.5rem;
}

:deep(.p-checkbox) {
    width: 1.25rem;
    height: 1.25rem;
}

:deep(.p-checkbox-box) {
    border-radius: 0.25rem;
    border: 2px solid #dee2e6;
    transition: all 0.2s;
}

:deep(.p-checkbox:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box) {
    border-color: #667eea;
}

:deep(.p-checkbox-box.p-highlight) {
    background: #667eea;
    border-color: #667eea;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: transform 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:active {
    transform: translateY(0);
}

a:hover {
    color: #667eea !important;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .card-body {
        padding: 2rem !important;
    }
}
</style>
