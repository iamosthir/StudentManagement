<template>
  <div class="transfer-form-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="bi bi-arrow-left-right gradient-icon"></i>
          Transfer Money
        </h1>
        <p class="page-subtitle">Transfer money between admin wallets</p>
      </div>
      <Button
        label="Back to Transfers"
        icon="bi bi-arrow-left"
        @click="router.push('/transfers')"
        severity="secondary"
        outlined
      />
    </div>

    <!-- Transfer Form -->
    <form @submit.prevent="submitTransfer" class="transfer-form">
      <!-- Transfer Details Section -->
      <div class="form-section" :class="{ 'section-animate': formLoaded }">
        <div class="section-header">
          <div class="section-icon primary">
            <i class="bi bi-send-fill"></i>
          </div>
          <div>
            <h3 class="section-title">Transfer Details</h3>
            <p class="section-subtitle">Enter the transfer amount and recipient</p>
          </div>
        </div>

        <div class="section-body">
          <div class="form-row">
            <div class="form-group">
              <label for="from_wallet_id">
                <i class="bi bi-wallet-fill"></i>
                From Your Wallet <span class="required">*</span>
              </label>
              <Select
                v-model="form.from_wallet_id"
                :options="myWallets"
                optionLabel="name"
                optionValue="id"
                placeholder="Select your wallet"
                :class="{ 'p-invalid': errors.from_wallet_id }"
                :loading="loadingMyWallets"
              >
                <template #option="slotProps">
                  <div class="flex flex-column">
                    <div class="font-medium">{{ slotProps.option.name }}</div>
                    <div class="text-sm text-gray-500">{{ slotProps.option.type_label }}</div>
                    <div class="text-sm text-green-600">Balance: ${{ slotProps.option.balance }}</div>
                  </div>
                </template>
              </Select>
              <small v-if="errors.from_wallet_id" class="error-message">
                {{ errors.from_wallet_id }}
              </small>
              <small v-else-if="selectedFromWallet" class="hint-text">
                Available balance: <strong>${{ selectedFromWallet.balance }}</strong>
              </small>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="to_admin_id">
                <i class="bi bi-person-fill"></i>
                Recipient Admin <span class="required">*</span>
              </label>
              <Select
                v-model="form.to_admin_id"
                :options="admins"
                optionLabel="name"
                optionValue="id"
                placeholder="Select recipient admin"
                :class="{ 'p-invalid': errors.to_admin_id }"
                :loading="loadingAdmins"
                @change="onRecipientChange"
              >
                <template #value="slotProps">
                  <div v-if="slotProps.value" class="flex align-items-center">
                    <div>{{ getAdminName(slotProps.value) }}</div>
                  </div>
                  <span v-else>{{ slotProps.placeholder }}</span>
                </template>
                <template #option="slotProps">
                  <div class="flex flex-column">
                    <div class="font-medium">{{ slotProps.option.name }}</div>
                    <div class="text-sm text-gray-500">{{ slotProps.option.email }}</div>
                  </div>
                </template>
              </Select>
              <small v-if="errors.to_admin_id" class="error-message">
                {{ errors.to_admin_id }}
              </small>
            </div>

            <div class="form-group" v-if="form.to_admin_id">
              <label for="to_wallet_id">
                <i class="bi bi-wallet2"></i>
                To Their Wallet <span class="required">*</span>
              </label>
              <Select
                v-model="form.to_wallet_id"
                :options="recipientWallets"
                optionLabel="name"
                optionValue="id"
                placeholder="Select recipient wallet"
                :class="{ 'p-invalid': errors.to_wallet_id }"
                :loading="loadingRecipientWallets"
              >
                <template #option="slotProps">
                  <div class="flex flex-column">
                    <div class="font-medium">{{ slotProps.option.name }}</div>
                    <div class="text-sm text-gray-500">{{ slotProps.option.type_label }}</div>
                  </div>
                </template>
              </Select>
              <small v-if="errors.to_wallet_id" class="error-message">
                {{ errors.to_wallet_id }}
              </small>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="amount">
                <i class="bi bi-cash-stack"></i>
                Amount <span class="required">*</span>
              </label>
              <InputNumber
                v-model="form.amount"
                inputId="amount"
                :minFractionDigits="2"
                :maxFractionDigits="2"
                :min="0.01"
                placeholder="0.00"
                :class="{ 'p-invalid': errors.amount }"
              />
              <small v-if="errors.amount" class="error-message">
                {{ errors.amount }}
              </small>
            </div>
          </div>

          <div class="form-group">
            <label for="notes">
              <i class="bi bi-card-text"></i>
              Notes
            </label>
            <Textarea
              v-model="form.notes"
              inputId="notes"
              rows="4"
              placeholder="Add any notes for this transfer..."
              :class="{ 'p-invalid': errors.notes }"
            />
            <small v-if="errors.notes" class="error-message">
              {{ errors.notes }}
            </small>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="form-actions">
        <Button
          type="button"
          label="Cancel"
          severity="secondary"
          outlined
          @click="router.push('/transfers')"
          :disabled="submitting"
        />
        <Button
          type="submit"
          :label="submitting ? 'Processing...' : 'Transfer Money'"
          icon="bi bi-arrow-right"
          iconPos="right"
          :loading="submitting"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

const router = useRouter();

const form = ref({
  from_wallet_id: null,
  to_admin_id: null,
  to_wallet_id: null,
  amount: null,
  notes: ''
});

const errors = ref({});
const submitting = ref(false);
const formLoaded = ref(false);
const admins = ref([]);
const loadingAdmins = ref(false);
const currentAdmin = ref(null);
const myWallets = ref([]);
const loadingMyWallets = ref(false);
const recipientWallets = ref([]);
const loadingRecipientWallets = ref(false);

const selectedFromWallet = computed(() => {
  return myWallets.value.find(w => w.id === form.value.from_wallet_id);
});

onMounted(async () => {
  setTimeout(() => {
    formLoaded.value = true;
  }, 100);

  await Promise.all([
    fetchAdmins(),
    fetchCurrentAdmin(),
    fetchMyWallets()
  ]);
});

const fetchAdmins = async () => {
  loadingAdmins.value = true;
  try {
    const response = await axios.get('/admin/wallet-transfers/admins');
    admins.value = response.data.admins;
  } catch (error) {
    console.error('Error fetching admins:', error);
  } finally {
    loadingAdmins.value = false;
  }
};

const fetchCurrentAdmin = async () => {
  try {
    const response = await axios.get('/admin/me');
    currentAdmin.value = response.data.admin;
  } catch (error) {
    console.error('Error fetching current admin:', error);
  }
};

const fetchMyWallets = async () => {
  loadingMyWallets.value = true;
  try {
    const response = await axios.get('/admin/wallets/my-wallet');
    if (response.data.success) {
      myWallets.value = response.data.data.wallets.map(wallet => ({
        ...wallet,
        type_label: formatWalletType(wallet.type),
        balance: parseFloat(wallet.balance).toFixed(2)
      }));
    }
  } catch (error) {
    console.error('Error fetching my wallets:', error);
  } finally {
    loadingMyWallets.value = false;
  }
};

const fetchRecipientWallets = async (adminId) => {
  loadingRecipientWallets.value = true;
  try {
    const response = await axios.get(`/admin/wallets/user-wallets/${adminId}`);
    if (response.data.success) {
      recipientWallets.value = response.data.data.map(wallet => ({
        ...wallet,
        type_label: formatWalletType(wallet.type)
      }));
    }
  } catch (error) {
    console.error('Error fetching recipient wallets:', error);
    recipientWallets.value = [];
  } finally {
    loadingRecipientWallets.value = false;
  }
};

const onRecipientChange = () => {
  form.value.to_wallet_id = null;
  recipientWallets.value = [];
  if (form.value.to_admin_id) {
    fetchRecipientWallets(form.value.to_admin_id);
  }
};

const getAdminName = (adminId) => {
  const admin = admins.value.find(a => a.id === adminId);
  return admin ? admin.name : '';
};

const formatWalletType = (type) => {
  const types = {
    staff: 'Staff Wallet',
    main_cashbox: 'Main Cashbox',
    expense: 'Expense Wallet'
  };
  return types[type] || type;
};

const submitTransfer = async () => {
  errors.value = {};

  // Validate wallet balance
  if (selectedFromWallet.value && form.value.amount) {
    const walletBalance = parseFloat(selectedFromWallet.value.balance);
    const transferAmount = parseFloat(form.value.amount);

    if (transferAmount > walletBalance) {
      errors.value.amount = `Insufficient balance. Available: $${walletBalance.toFixed(2)}`;
      return;
    }
  }

  submitting.value = true;

  try {
    const response = await axios.post('/admin/wallet-transfers/direct-transfer', form.value);

    alert(response.data.message);
    router.push('/transfers');
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
    } else {
      alert(error.response?.data?.message || 'An error occurred while processing the transfer.');
    }
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.transfer-form-container {
  padding: 2rem;
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header-content {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.gradient-icon {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.page-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.transfer-form {
  background: white;
  border-radius: 16px;
  padding: 0;
}

.form-section {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  opacity: 0;
  transform: translateY(20px);
}

.form-section.section-animate {
  opacity: 1;
  transform: translateY(0);
}

.form-section:hover {
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
  transform: translateY(-4px);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
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
  font-size: 1.5rem;
  color: white;
  animation: float 3s ease-in-out infinite;
}

.section-icon.primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.section-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0 0;
}

.section-body {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #334155;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-group label i {
  color: #6366f1;
}

.required {
  color: #ef4444;
}

.error-message {
  color: #ef4444;
  font-size: 0.875rem;
}

.hint-text {
  color: #64748b;
  font-size: 0.875rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  background: #f8fafc;
  border-radius: 0 0 16px 16px;
  margin-top: -1.5rem;
}

@media (max-width: 768px) {
  .transfer-form-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
