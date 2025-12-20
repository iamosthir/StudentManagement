<template>
    <div class="students">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
                <div>
                    <h1 class="page-title">
                        <i v-if="statusFilter === 'archived'" class="bi bi-archive me-2"></i>
                        {{ statusFilter === 'archived' ? 'Student Archive' : 'Students' }}
                    </h1>
                    <p v-if="statusFilter === 'archived'" class="page-subtitle">
                        View and restore archived students
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <router-link
                        v-if="statusFilter === 'archived'"
                        :to="{ name: 'students.index' }"
                        class="btn-secondary"
                    >
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Students
                    </router-link>
                    <router-link v-if="statusFilter !== 'archived'" :to="{ name: 'students.create' }" class="btn-primary">
                        Add Student
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="glass-card mb-4">
            <div class="glass-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                v-model="search"
                                @keyup.enter="handleSearch"
                                placeholder="Search by name, admission number, phone..."
                                class="search-input"
                            />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select v-model="statusFilter" class="filter-select" @change="handleSearch">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="pending_payment">Pending Payment</option>
                            <option value="expired">Expired</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select v-model="programFilter" class="filter-select" @change="handleSearch">
                            <option value="">All Programs</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn-filter-reset" @click="resetFilters" title="Reset Filters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h6 class="card-title">
                    {{ statusFilter === 'archived' ? 'Archived Students' : 'All Students' }}
                </h6>
                <div class="header-actions">
                    <span class="student-count">{{ pagination.total || 0 }} {{ statusFilter === 'archived' ? 'Archived' : '' }} Students</span>
                </div>
            </div>
            <div class="glass-card-body">
                <!-- Loading State -->
                <TableSkeleton v-if="loading" :rows="10" :columns="6" />

                <!-- Empty State -->
                <div v-else-if="students.length === 0" class="empty-state">
                    <i class="bi bi-people"></i>
                    <h3>No Students Found</h3>
                    <p>Start by adding your first student</p>
                    <router-link :to="{ name: 'students.create' }" class="btn-primary">
                        Add Student
                    </router-link>
                </div>

                <!-- Table -->
                <div v-else class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Admission Number</th>
                                <th>Student Name</th>
                                <th>Phone</th>
                                <th>Program</th>
                                <th>Subscriptions</th>
                                <th>Total Payable</th>
                                <th>Total Paid</th>
                                <th>Total Due</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="student.id">
                                <td>
                                    <span class="table-id">{{ student.admission_number }}</span>
                                </td>
                                <td>
                                    <div class="student-info">
                                        <span class="student-name fw-medium">{{ student.full_name }}</span>
                                        <small v-if="student.email" class="text-muted d-block">
                                            <i class="bi bi-envelope me-1"></i>{{ student.email }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="phone-number">
                                        <i class="bi bi-telephone me-1"></i>{{ student.phone }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="student.program" class="program-badge">
                                        <i class="bi bi-book me-1"></i>{{ student.program.name }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>
                                    <div v-if="student.subscriptions && student.subscriptions.length > 0" class="subscriptions-badges">
                                        <span
                                            v-for="sub in student.subscriptions"
                                            :key="sub.id"
                                            :class="['subscription-badge', getSubscriptionStatusClass(sub), 'clickable-badge']"
                                            :title="`${sub.subscription_option?.name} - Expires: ${formatDate(sub.expiry_date)} - Click to renew`"
                                            @click.stop="openRenewalModal(sub, student)"
                                        >
                                            <i :class="getSubscriptionIcon(sub)"></i>
                                            {{ sub.subscription_option?.name || 'Subscription' }}
                                        </span>
                                    </div>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>
                                    <span class="financial-value payable">
                                        ${{ formatMoney(student.total_subscription_cost) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="financial-value paid">
                                        ${{ formatMoney(student.total_paid_amount) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="financial-value due">
                                        ${{ formatMoney(student.total_due_amount) }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="['status-badge', getStatusClass(student.status)]">
                                        {{ getStatusLabel(student.status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button
                                            @click="openPaymentModal(student)"
                                            class="btn-action btn-action-payment"
                                            title="Add Payment"
                                        >
                                            <i class="bi bi-cash-stack"></i>
                                        </button>
                                        <router-link
                                            :to="{ name: 'students.show', params: { id: student.id } }"
                                            class="btn-action btn-action-view"
                                            title="View Details"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </router-link>
                                        <router-link
                                            :to="{ name: 'students.edit', params: { id: student.id } }"
                                            class="btn-action btn-action-edit"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </router-link>
                                        <button
                                            v-if="student.status === 'archived'"
                                            @click="confirmRestore(student)"
                                            class="btn-action btn-action-restore"
                                            title="Restore"
                                        >
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                        <button
                                            v-if="student.status !== 'archived'"
                                            @click="confirmArchive(student)"
                                            class="btn-action btn-action-archive"
                                            title="Archive"
                                        >
                                            <i class="bi bi-archive"></i>
                                        </button>
                                        <button
                                            @click="confirmDelete(student)"
                                            class="btn-action btn-action-delete"
                                            title="Delete"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="pagination-wrapper">
                    <button
                        @click="fetchStudents(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="pagination-btn"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </button>

                    <span class="pagination-info">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </span>

                    <button
                        @click="fetchStudents(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="pagination-btn"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div v-if="showArchiveModal" class="modal-overlay" @click="showArchiveModal = false">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Archive</h5>
                    <button @click="showArchiveModal = false" class="btn-close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon modal-icon-warning">
                        <i class="bi bi-archive"></i>
                    </div>
                    <p>Are you sure you want to archive <strong>{{ studentToArchive?.full_name }}</strong>?</p>
                    <p class="text-muted">Archived students can be restored later.</p>
                </div>
                <div class="modal-footer">
                    <button @click="showArchiveModal = false" class="btn-secondary">Cancel</button>
                    <button @click="archiveStudent" class="btn-warning" :disabled="archiving">
                        <i v-if="archiving" class="bi bi-arrow-repeat spinner me-2"></i>
                        {{ archiving ? 'Archiving...' : 'Archive' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal -->
        <div v-if="showRestoreModal" class="modal-overlay" @click="showRestoreModal = false">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Restore</h5>
                    <button @click="showRestoreModal = false" class="btn-close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon modal-icon-success">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <p>Are you sure you want to restore <strong>{{ studentToRestore?.full_name }}</strong>?</p>
                    <p class="text-muted">The student will be moved back to active students.</p>
                </div>
                <div class="modal-footer">
                    <button @click="showRestoreModal = false" class="btn-secondary">Cancel</button>
                    <button @click="restoreStudent" class="btn-success" :disabled="restoring">
                        <i v-if="restoring" class="bi bi-arrow-repeat spinner me-2"></i>
                        {{ restoring ? 'Restoring...' : 'Restore' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button @click="showDeleteModal = false" class="btn-close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon modal-icon-danger">
                        <i class="bi bi-trash"></i>
                    </div>
                    <p>Are you sure you want to delete <strong>{{ studentToDelete?.full_name }}</strong>?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button @click="showDeleteModal = false" class="btn-secondary">Cancel</button>
                    <button @click="deleteStudent" class="btn-danger" :disabled="deleting">
                        <i v-if="deleting" class="bi bi-arrow-repeat spinner me-2"></i>
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Renewal Modal -->
        <Dialog
            v-model:visible="showRenewalModal"
            modal
            header="Renew Subscription"
            :style="{ width: '90vw', maxWidth: '600px', zIndex: 9999 }"
            :dismissableMask="true"
            :closable="true"
        >
            <div class="renewal-modal-content">
                <form @submit.prevent="handleRenewalSubmit">
                    <!-- Subscription Info Section -->
                    <div class="renewal-section">
                        <div class="renewal-info-card">
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-person"></i> Student:</span>
                                <span class="info-value">{{ selectedStudent?.full_name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-calendar-check"></i> Current Expiry:</span>
                                <span class="info-value">{{ formatDate(selectedSubscription?.expiry_date) }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-clock-history"></i> Remaining Days:</span>
                                <span class="info-value" :class="selectedSubscription?.remaining_days <= 7 ? 'text-warning' : ''">
                                    {{ selectedSubscription?.remaining_days || 0 }} days
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="bi bi-currency-dollar"></i> Current Price:</span>
                                <span class="info-value">${{ parseFloat(selectedSubscription?.final_price || 0).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Renewal Details Section -->
                    <div class="renewal-section">
                        <h4 class="section-title">
                            <i class="bi bi-arrow-clockwise"></i>
                            Renewal Details
                        </h4>

                        <div class="form-grid">
                            <!-- Duration -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-calendar-range"></i>
                                    Extension Duration (Months)
                                    <span class="required">*</span>
                                </label>
                                <InputNumber
                                    v-model="renewalForm.duration_months"
                                    :min="1"
                                    :max="120"
                                    showButtons
                                    buttonLayout="horizontal"
                                    decrementButtonClass="p-button-secondary"
                                    incrementButtonClass="p-button-secondary"
                                    incrementButtonIcon="bi bi-plus"
                                    decrementButtonIcon="bi bi-dash"
                                />
                                <small class="form-hint">
                                    Default: {{ selectedSubscription?.subscription_option?.duration_months }} months
                                </small>
                            </div>

                            <!-- Renewal Price -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-cash-coin"></i>
                                    Renewal Price
                                    <span class="required">*</span>
                                </label>
                                <InputNumber
                                    v-model="renewalForm.renewal_price"
                                    mode="currency"
                                    currency="USD"
                                    locale="en-US"
                                    :min="0"
                                    :maxFractionDigits="2"
                                />
                                <small class="form-hint">
                                    Default: ${{ parseFloat(selectedSubscription?.final_price || 0).toFixed(2) }}
                                </small>
                            </div>

                            <!-- New Expiry Date (Calculated) -->
                            <div class="form-group form-group-full">
                                <label class="form-label">
                                    <i class="bi bi-calendar-event"></i>
                                    New Expiry Date
                                </label>
                                <div class="calculated-expiry">
                                    <i class="bi bi-calendar-check-fill"></i>
                                    <span class="expiry-date">{{ calculateNewExpiryDate }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Options Section -->
                    <div class="renewal-section">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <Checkbox
                                    v-model="renewalForm.create_payment"
                                    inputId="create_payment"
                                    :binary="true"
                                />
                                <label for="create_payment" class="checkbox-label">
                                    Create payment record for this renewal
                                </label>
                            </div>
                        </div>

                        <!-- Payment Details (shown when create_payment is true) -->
                        <div v-if="renewalForm.create_payment" class="payment-details">
                            <div class="form-grid">
                                <!-- Payment Method -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-credit-card"></i>
                                        Payment Method
                                        <span class="required">*</span>
                                    </label>
                                    <Select
                                        v-model="renewalForm.payment_method"
                                        :options="paymentMethods"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Select Payment Method"
                                    />
                                </div>

                                <!-- Payment Status -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-check-circle"></i>
                                        Payment Status
                                        <span class="required">*</span>
                                    </label>
                                    <Select
                                        v-model="renewalForm.payment_status"
                                        :options="paymentStatuses"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Select Status"
                                    />
                                </div>

                                <!-- Payment Note -->
                                <div class="form-group form-group-full">
                                    <label class="form-label">
                                        <i class="bi bi-pencil-square"></i>
                                        Payment Note
                                    </label>
                                    <Textarea
                                        v-model="renewalForm.payment_note"
                                        rows="2"
                                        placeholder="Optional payment notes..."
                                        autoResize
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="modal-actions">
                        <Button
                            label="Cancel"
                            severity="secondary"
                            outlined
                            @click="closeRenewalModal"
                            type="button"
                        />
                        <Button
                            label="Renew Subscription"
                            icon="bi bi-arrow-clockwise"
                            :loading="submittingRenewal"
                            type="submit"
                        />
                    </div>
                </form>
            </div>
        </Dialog>

        <!-- Add Payment Modal -->
        <Dialog
            v-model:visible="showPaymentModal"
            modal
            :header="`Add Payment - ${selectedStudentForPayment?.full_name}`"
            :style="{ width: '90vw', maxWidth: '1000px' }"
            :contentStyle="{ padding: 0 }"
        >
            <div class="payment-modal-content">
                <form @submit.prevent="handlePaymentSubmit">
                    <!-- Payment Information Section -->
                    <div class="payment-section">
                        <div class="section-header-small">
                            <i class="bi bi-info-circle-fill"></i>
                            <h3>Payment Information</h3>
                        </div>
                        <div class="section-body-small">
                            <div class="form-grid-2">
                                <!-- Payment Method -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-credit-card"></i>
                                        Payment Method
                                    </label>
                                    <Select
                                        v-model="paymentForm.payment_method"
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
                                        v-model="paymentForm.status"
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
                                        v-model="paymentForm.note"
                                        rows="3"
                                        placeholder="Optional payment notes..."
                                        autoResize
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Items Section -->
                    <div class="payment-section">
                        <div class="section-header-small">
                            <i class="bi bi-cart-check-fill"></i>
                            <h3>Payment Items</h3>
                            <Button
                                label="Add Item"
                                icon="bi bi-plus-circle"
                                severity="success"
                                size="small"
                                @click="addPaymentItem"
                                type="button"
                            />
                        </div>
                        <div class="section-body-small">
                            <!-- Items List -->
                            <div class="payment-items-list">
                                <div v-for="(item, index) in paymentItems" :key="index" class="payment-item-card">
                                    <div class="item-header-small">
                                        <span class="item-number">Item {{ index + 1 }}</span>
                                        <Button
                                            v-if="paymentItems.length > 1"
                                            icon="bi bi-trash"
                                            severity="danger"
                                            text
                                            rounded
                                            size="small"
                                            @click="removePaymentItem(index)"
                                            type="button"
                                        />
                                    </div>

                                    <!-- Payment Info Card -->
                                    <div v-if="item.payment_info" class="payment-info-small">
                                        <div class="info-grid">
                                            <div class="info-item">
                                                <span class="info-label">Total Price:</span>
                                                <span class="info-value">${{ item.payment_info.total_price.toFixed(2) }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Already Paid:</span>
                                                <span class="info-value success">${{ item.payment_info.paid_amount.toFixed(2) }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Remaining:</span>
                                                <span class="info-value warning">${{ item.payment_info.remaining_amount.toFixed(2) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="item.payment_info.is_full_payment" class="info-alert">
                                            <i class="bi bi-exclamation-circle"></i>
                                            Full payment required: ${{ item.payment_info.remaining_amount.toFixed(2) }}
                                        </div>
                                        <div v-else class="info-alert info">
                                            <i class="bi bi-info-circle"></i>
                                            Partial payment allowed: $1 - ${{ item.payment_info.remaining_amount.toFixed(2) }}
                                        </div>
                                    </div>

                                    <div v-if="item.loading_info" class="loading-info-small">
                                        <i class="bi bi-hourglass-split"></i>
                                        <span>Loading payment information...</span>
                                    </div>

                                    <div class="form-grid-2">
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
                                                :disabled="item.item_type === 'subscription' && !selectedStudentForPayment"
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
                                            <div class="total-price-display" :class="{ 'has-error': paymentErrors[`items.${index}.total_price`] }">
                                                <span class="currency-symbol">$</span>
                                                <span class="price-value">{{ parseFloat(item.total_price || 0).toFixed(2) }}</span>
                                            </div>
                                            <small v-if="paymentErrors[`items.${index}.total_price`]" class="error-message">
                                                {{ paymentErrors[`items.${index}.total_price`][0] }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Summary -->
                            <div class="payment-summary-small">
                                <span class="summary-label">
                                    <i class="bi bi-calculator"></i>
                                    Total Amount
                                </span>
                                <span class="summary-value">
                                    ${{ totalPaymentAmount.toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="modal-actions">
                        <Button
                            label="Cancel"
                            severity="secondary"
                            outlined
                            @click="closePaymentModal"
                            type="button"
                        />
                        <Button
                            label="Create Payment"
                            icon="bi bi-check-circle"
                            :loading="submittingPayment"
                            type="submit"
                            :disabled="paymentItems.length === 0 || !selectedStudentForPayment"
                        />
                    </div>
                </form>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import TableSkeleton from '@/components/TableSkeleton.vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';

const route = useRoute();
const students = ref([]);
const loading = ref(false);
const search = ref('');
const statusFilter = ref('');
const programFilter = ref('');
const programs = ref([]);

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
});

const showArchiveModal = ref(false);
const studentToArchive = ref(null);
const archiving = ref(false);

const showRestoreModal = ref(false);
const studentToRestore = ref(null);
const restoring = ref(false);

const showDeleteModal = ref(false);
const studentToDelete = ref(null);
const deleting = ref(false);

// Renewal Modal
const showRenewalModal = ref(false);
const selectedSubscription = ref(null);
const selectedStudent = ref(null);
const submittingRenewal = ref(false);

const renewalForm = ref({
    duration_months: null,
    renewal_price: null,
    create_payment: false,
    payment_method: 'cash',
    payment_status: 'paid',
    payment_note: ''
});

// Payment Modal
const showPaymentModal = ref(false);
const selectedStudentForPayment = ref(null);
const submittingPayment = ref(false);
const paymentErrors = ref({});
const subscriptions = ref([]);
const products = ref([]);

const paymentForm = ref({
    student_id: null,
    payment_method: 'cash',
    note: '',
    status: 'paid'
});

const paymentItems = ref([
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

const totalPaymentAmount = computed(() => {
    return paymentItems.value.reduce((sum, item) => sum + parseFloat(item.total_price || 0), 0);
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

// Fetch students
const fetchStudents = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            per_page: pagination.value.per_page,
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            program_id: programFilter.value || undefined
        };

        const response = await axios.get('/admin/students', { params });
        students.value = response.data.data;
        pagination.value = response.data.meta;
    } catch (error) {
        console.error('Error fetching students:', error);
    } finally {
        loading.value = false;
    }
};

// Fetch programs
const fetchPrograms = async () => {
    try {
        const response = await axios.get('/admin/programs');
        programs.value = response.data.data;
    } catch (error) {
        console.error('Error fetching programs:', error);
    }
};

// Search
const handleSearch = () => {
    fetchStudents(1);
};

// Reset filters
const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    programFilter.value = '';
    fetchStudents(1);
};

// Confirm archive
const confirmArchive = (student) => {
    studentToArchive.value = student;
    showArchiveModal.value = true;
};

// Archive student
const archiveStudent = async () => {
    archiving.value = true;
    try {
        await axios.post(`/admin/students/${studentToArchive.value.id}/archive`);
        showArchiveModal.value = false;
        studentToArchive.value = null;
        fetchStudents(pagination.value.current_page);
    } catch (error) {
        console.error('Error archiving student:', error);
        alert(error.response?.data?.message || 'Failed to archive student');
    } finally {
        archiving.value = false;
    }
};

// Confirm restore
const confirmRestore = (student) => {
    studentToRestore.value = student;
    showRestoreModal.value = true;
};

// Restore student
const restoreStudent = async () => {
    restoring.value = true;
    try {
        await axios.post(`/admin/students/${studentToRestore.value.id}/restore`);
        showRestoreModal.value = false;
        studentToRestore.value = null;
        fetchStudents(pagination.value.current_page);
    } catch (error) {
        console.error('Error restoring student:', error);
        alert(error.response?.data?.message || 'Failed to restore student');
    } finally {
        restoring.value = false;
    }
};

// Confirm delete
const confirmDelete = (student) => {
    studentToDelete.value = student;
    showDeleteModal.value = true;
};

// Delete student
const deleteStudent = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/admin/students/${studentToDelete.value.id}`);
        showDeleteModal.value = false;
        studentToDelete.value = null;
        fetchStudents(pagination.value.current_page);
    } catch (error) {
        console.error('Error deleting student:', error);
        alert(error.response?.data?.message || 'Failed to delete student');
    } finally {
        deleting.value = false;
    }
};

// Status helpers
const getStatusClass = (status) => {
    const classes = {
        active: 'status-active',
        pending_payment: 'status-pending',
        expired: 'status-expired',
        archived: 'status-archived'
    };
    return classes[status] || 'status-secondary';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Active',
        pending_payment: 'Pending Payment',
        expired: 'Expired',
        archived: 'Archived'
    };
    return labels[status] || status;
};

// Subscription status helpers
const getSubscriptionStatusClass = (subscription) => {
    if (!subscription.expiry_date) return 'subscription-active';
    const expiryDate = new Date(subscription.expiry_date);
    const now = new Date();
    return expiryDate > now ? 'subscription-active' : 'subscription-expired';
};

const getSubscriptionIcon = (subscription) => {
    if (!subscription.expiry_date) return 'bi bi-check-circle-fill';
    const expiryDate = new Date(subscription.expiry_date);
    const now = new Date();
    return expiryDate > now ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill';
};

// Format helpers
const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatMoney = (value) => {
    return parseFloat(value || 0).toFixed(2);
};

// Renewal Modal Methods
const openRenewalModal = (subscription, student) => {
    selectedSubscription.value = subscription;
    selectedStudent.value = student;

    // Set default values
    renewalForm.value = {
        duration_months: subscription.subscription_option?.duration_months || 1,
        renewal_price: parseFloat(subscription.final_price || 0),
        create_payment: false,
        payment_method: 'cash',
        payment_status: 'paid',
        payment_note: ''
    };

    showRenewalModal.value = true;
};

const closeRenewalModal = () => {
    showRenewalModal.value = false;
    selectedSubscription.value = null;
    selectedStudent.value = null;
    renewalForm.value = {
        duration_months: null,
        renewal_price: null,
        create_payment: false,
        payment_method: 'cash',
        payment_status: 'paid',
        payment_note: ''
    };
};

const calculateNewExpiryDate = computed(() => {
    if (!selectedSubscription.value?.expiry_date || !renewalForm.value.duration_months) {
        return 'N/A';
    }

    const currentExpiry = new Date(selectedSubscription.value.expiry_date);
    const newExpiry = new Date(currentExpiry);
    newExpiry.setMonth(newExpiry.getMonth() + parseInt(renewalForm.value.duration_months));

    return formatDate(newExpiry);
});

const handleRenewalSubmit = async () => {
    submittingRenewal.value = true;

    try {
        const payload = {
            duration_months: renewalForm.value.duration_months,
            renewal_price: renewalForm.value.renewal_price,
            create_payment: renewalForm.value.create_payment,
        };

        // Add payment details if creating payment
        if (renewalForm.value.create_payment) {
            payload.payment_method = renewalForm.value.payment_method;
            payload.payment_status = renewalForm.value.payment_status;
            payload.payment_note = renewalForm.value.payment_note;
        }

        const response = await axios.post(`/admin/subscriptions/${selectedSubscription.value.id}/renew`, payload);

        alert(response.data.message || 'Subscription renewed successfully!');
        closeRenewalModal();

        // Refresh the student list to show updated data
        fetchStudents(pagination.value.current_page);
    } catch (error) {
        console.error('Error renewing subscription:', error);
        alert(error.response?.data?.message || 'Failed to renew subscription');
    } finally {
        submittingRenewal.value = false;
    }
};

// Payment Modal Methods
const openPaymentModal = async (student) => {
    selectedStudentForPayment.value = student;
    paymentForm.value.student_id = student.id;
    showPaymentModal.value = true;

    // Fetch student subscriptions and products
    await Promise.all([
        fetchStudentSubscriptions(student.id),
        fetchProducts()
    ]);
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedStudentForPayment.value = null;
    paymentForm.value = {
        student_id: null,
        payment_method: 'cash',
        note: '',
        status: 'paid'
    };
    paymentItems.value = [{
        item_type: 'subscription',
        item_id: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_value: 0,
        total_price: 0,
        payment_info: null,
        loading_info: false
    }];
    paymentErrors.value = {};
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

const fetchPaymentInfo = async (item) => {
    if (!paymentForm.value.student_id || !item.item_id || !item.item_type) {
        return;
    }

    item.loading_info = true;

    try {
        const response = await axios.post('/admin/payments/item-payment-info', {
            student_id: paymentForm.value.student_id,
            item_type: item.item_type,
            item_id: item.item_id
        });

        item.payment_info = response.data.data;

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

const addPaymentItem = () => {
    paymentItems.value.push({
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

const removePaymentItem = (index) => {
    if (paymentItems.value.length > 1) {
        paymentItems.value.splice(index, 1);
    }
};

const calculateItemTotal = (item) => {
    const subtotal = parseFloat(item.unit_price || 0) * parseInt(item.quantity || 1);
    const discount = parseFloat(item.discount_value || 0);
    let calculatedTotal = (subtotal - discount);

    if (item.payment_info) {
        if (item.payment_info.is_full_payment) {
            calculatedTotal = item.payment_info.remaining_amount;
        } else {
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

const validateTotalPrice = (item) => {
    if (!item.payment_info) return true;

    const totalPrice = parseFloat(item.total_price || 0);

    if (item.payment_info.is_full_payment) {
        if (totalPrice !== parseFloat(item.payment_info.remaining_amount)) {
            return false;
        }
    } else {
        if (totalPrice < item.payment_info.min_payment || totalPrice > item.payment_info.max_payment) {
            return false;
        }
    }

    return true;
};

const handlePaymentSubmit = async () => {
    paymentErrors.value = {};

    // Validate payment amounts
    let hasErrors = false;
    paymentItems.value.forEach((item, index) => {
        if (!validateTotalPrice(item)) {
            if (item.payment_info?.is_full_payment) {
                paymentErrors.value[`items.${index}.total_price`] = [`Full payment required: $${item.payment_info.remaining_amount}`];
            } else {
                paymentErrors.value[`items.${index}.total_price`] = [`Amount must be between $${item.payment_info?.min_payment} and $${item.payment_info?.max_payment}`];
            }
            hasErrors = true;
        }
    });

    if (hasErrors) {
        alert('Please fix the payment amount errors before submitting');
        return;
    }

    submittingPayment.value = true;

    try {
        const payload = {
            ...paymentForm.value,
            items: paymentItems.value.map(item => ({
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
        closePaymentModal();
        fetchStudents(pagination.value.current_page);
    } catch (error) {
        if (error.response?.data?.errors) {
            paymentErrors.value = error.response.data.errors;
        } else {
            alert(error.response?.data?.message || 'Failed to create payment');
        }
    } finally {
        submittingPayment.value = false;
    }
};

// Watch for payment item changes
watch(paymentItems, (newItems) => {
    newItems.forEach(item => {
        watch([() => item.unit_price, () => item.quantity, () => item.discount_value], () => {
            calculateItemTotal(item);
        });
    });
}, { deep: true, immediate: true });

// Watch for route changes
watch(() => route.query.status, (newStatus) => {
    statusFilter.value = newStatus || '';
    fetchStudents(1);
}, { immediate: false });

onMounted(() => {
    // Check for query parameters from route
    if (route.query.status) {
        statusFilter.value = route.query.status;
    }

    fetchStudents();
    fetchPrograms();
});
</script>

<style scoped>
/* Subscription Badges */
.subscriptions-badges {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.subscription-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.subscription-badge.subscription-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.subscription-badge.subscription-expired {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.subscription-badge i {
    font-size: 0.85em;
}

/* Financial Values */
.financial-value {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.9rem;
}

.financial-value.payable {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
}

.financial-value.paid {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.financial-value.due {
    background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
    color: #9a3412;
}

/* Payment Action Button */
.btn-action-payment {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-action-payment:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Payment Modal Content */
.payment-modal-content {
    padding: 0;
}

.payment-section {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.payment-section:last-child {
    border-bottom: none;
}

.section-header-small {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.section-header-small i {
    font-size: 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.section-header-small h3 {
    flex: 1;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}

.section-body-small {
    padding-left: 0;
}

/* Form Grids */
.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.form-group-full {
    grid-column: 1 / -1;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #475569;
    font-size: 0.875rem;
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
    font-size: 0.75rem;
    margin-top: 4px;
}

/* Payment Items */
.payment-items-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 16px;
}

.payment-item-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
}

.item-header-small {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #cbd5e1;
}

.item-number {
    font-weight: 700;
    color: #6366f1;
    font-size: 0.9rem;
}

/* Payment Info Card */
.payment-info-small {
    background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
    border: 2px solid #fdba74;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 16px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 12px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #78350f;
}

.info-value {
    font-size: 1rem;
    font-weight: 700;
    color: #92400e;
}

.info-value.success {
    color: #065f46;
}

.info-value.warning {
    color: #9a3412;
}

.info-alert {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #fef3c7;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #78350f;
}

.info-alert.info {
    background: #dbeafe;
    color: #1e40af;
}

.info-alert i {
    font-size: 1rem;
}

/* Loading Info */
.loading-info-small {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #e0f2fe;
    border-radius: 8px;
    margin-bottom: 14px;
    color: #0369a1;
    font-weight: 500;
    font-size: 0.875rem;
}

.loading-info-small i {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Total Price Display */
.total-price-display {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #86efac;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1.1rem;
    color: #166534;
}

.total-price-display.has-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-color: #f87171;
    color: #991b1b;
}

.currency-symbol {
    font-size: 0.9em;
    opacity: 0.7;
}

/* Payment Summary */
.payment-summary-small {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    border: 2px solid #c4b5fd;
    border-radius: 12px;
    margin-top: 16px;
}

.summary-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #5b21b6;
    font-size: 1rem;
}

.summary-value {
    font-weight: 800;
    font-size: 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Modal Actions */
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid-2 {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}

/* Renewal Modal Styles */
.renewal-modal-content {
    padding: 0;
}

.renewal-section {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.renewal-section:last-child {
    border-bottom: none;
}

.renewal-info-card {
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    border: 2px solid #c4b5fd;
    border-radius: 12px;
    padding: 16px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e9d5ff;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .info-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #5b21b6;
    font-size: 0.9rem;
}

.info-row .info-label i {
    font-size: 1rem;
}

.info-row .info-value {
    font-weight: 700;
    color: #6366f1;
    font-size: 0.95rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}

.section-title i {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.form-hint {
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 4px;
}

.calculated-expiry {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border: 2px solid #6ee7b7;
    border-radius: 10px;
}

.calculated-expiry i {
    font-size: 1.5rem;
    color: #065f46;
}

.calculated-expiry .expiry-date {
    font-weight: 700;
    font-size: 1.1rem;
    color: #065f46;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #bae6fd;
    border-radius: 10px;
}

.checkbox-label {
    font-weight: 600;
    color: #0c4a6e;
    font-size: 0.95rem;
    cursor: pointer;
    margin: 0;
}

.payment-details {
    margin-top: 16px;
    padding: 16px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #fcd34d;
    border-radius: 10px;
}

.subscription-badge {
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.subscription-badge.clickable-badge {
    cursor: pointer;
    user-select: none;
}

.subscription-badge.clickable-badge:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.subscription-badge.clickable-badge:active {
    transform: scale(0.98);
}

/* Responsive for Renewal Modal */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
