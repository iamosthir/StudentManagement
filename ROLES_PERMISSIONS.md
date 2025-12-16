# Admin Roles & Permissions Guide

## Overview

This project uses **Spatie Laravel Permission** package for role-based access control (RBAC) with a separate `admin` guard.

## Installation & Setup

### 1. Install Package

```bash
composer require spatie/laravel-permission
```

### 2. Publish Configuration & Migrations

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 3. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `roles` - Stores role definitions
- `permissions` - Stores permission definitions
- `model_has_roles` - Pivot table for admin-role relationships
- `model_has_permissions` - Pivot table for admin-permission relationships
- `role_has_permissions` - Pivot table for role-permission relationships

### 4. Seed Roles & Permissions

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=AdminSeeder
```

## Default Roles

### Administrator
**Full access** to all system features and permissions.

**Login:**
- Email: `admin@example.com`
- Password: `password`

**All Permissions Assigned**

---

### Accountant
**Financial operations only** - manages payments, wallets, expenses, and reports.

**Login:**
- Email: `accountant@example.com`
- Password: `password`

**Permissions:**
- View students
- View/Create/Edit payments
- View/Manage wallets
- Create transfers
- View/Create/Edit/Delete expenses
- View/Export reports

---

### Registrar
**Student and subscription management** - handles student registration and enrollment.

**Login:**
- Email: `registrar@example.com`
- Password: `password`

**Permissions:**
- View/Create/Edit/Archive students
- View programs
- View/Create/Edit subscriptions
- View payments
- View reports

## Available Permissions

### Student Management
- `view students`
- `create students`
- `edit students`
- `delete students`
- `archive students`

### Program Management
- `view programs`
- `create programs`
- `edit programs`
- `delete programs`

### Subscription Management
- `view subscriptions`
- `create subscriptions`
- `edit subscriptions`
- `delete subscriptions`

### Payment Management
- `view payments`
- `create payments`
- `edit payments`
- `delete payments`

### Wallet Management
- `view wallets`
- `manage wallets`
- `create transfers`

### Expense Management
- `view expenses`
- `create expenses`
- `edit expenses`
- `delete expenses`

### User Management
- `view admins`
- `create admins`
- `edit admins`
- `delete admins`

### Role & Permission Management
- `view roles`
- `create roles`
- `edit roles`
- `delete roles`
- `assign roles`

### Reports
- `view reports`
- `export reports`

## Usage in Code

### Check if Admin Has Permission

```php
// In Controller
if ($admin->can('create students')) {
    // Admin can create students
}

// In Blade
@can('create students')
    <button>Add Student</button>
@endcan
```

### Check if Admin Has Role

```php
// In Controller
if ($admin->hasRole('Administrator')) {
    // Admin is an Administrator
}

// Check multiple roles
if ($admin->hasAnyRole(['Administrator', 'Accountant'])) {
    // Admin has at least one of these roles
}
```

### Assign Role to Admin

```php
$admin = Admin::find(1);
$admin->assignRole('Accountant');

// Assign multiple roles
$admin->assignRole(['Accountant', 'Registrar']);
```

### Remove Role from Admin

```php
$admin->removeRole('Accountant');
```

### Give Permission Directly to Admin

```php
$admin->givePermissionTo('create students');
```

### Middleware Protection

Protect routes with role/permission middleware:

```php
// In routes/web.php
Route::middleware(['auth:admin', 'role:Administrator'])->group(function () {
    // Only Administrator can access
});

Route::middleware(['auth:admin', 'permission:create students'])->group(function () {
    // Only admins with 'create students' permission
});

// Multiple permissions (admin must have ALL)
Route::middleware(['auth:admin', 'permission:create students|edit students'])->group(function () {
    // Requires both permissions
});
```

## Frontend Integration

When an admin logs in, the API returns their roles and permissions:

```json
{
  "success": true,
  "admin": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@example.com",
    "roles": ["Administrator"],
    "permissions": ["view students", "create students", ...]
  }
}
```

### In Vue Components

Check permissions before showing UI elements:

```vue
<script setup>
import { ref, computed, onMounted } from 'vue';

const adminUser = ref(null);

onMounted(() => {
    const storedUser = localStorage.getItem('admin_user');
    if (storedUser) {
        adminUser.value = JSON.parse(storedUser);
    }
});

const canCreateStudents = computed(() => {
    return adminUser.value?.permissions?.includes('create students');
});

const isAdministrator = computed(() => {
    return adminUser.value?.roles?.includes('Administrator');
});
</script>

<template>
    <button v-if="canCreateStudents">Add Student</button>
    <div v-if="isAdministrator">Admin Only Section</div>
</template>
```

## Creating New Roles

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::create([
    'name' => 'Content Manager',
    'guard_name' => 'admin'
]);

// Assign permissions to role
$role->givePermissionTo([
    'view students',
    'view programs',
    'view reports'
]);
```

## Creating New Permissions

```php
Permission::create([
    'name' => 'manage settings',
    'guard_name' => 'admin'
]);
```

## Important Notes

1. **Guard Name**: Always use `guard_name => 'admin'` for all roles and permissions since we're using a separate admin guard.

2. **Cache**: Spatie caches roles and permissions. After making changes programmatically, clear the cache:
   ```php
   app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
   ```

3. **Backend Enforcement**: Always enforce permissions on the backend (controllers/middleware). Frontend checks are for UX only.

## Useful Artisan Commands

```bash
# Clear permission cache
php artisan permission:cache-reset

# Show all permissions
php artisan permission:show

# Create permission
php artisan permission:create-permission "manage settings" --guard=admin

# Create role
php artisan permission:create-role "Content Manager" --guard=admin
```

## Documentation

For more details, visit: https://spatie.be/docs/laravel-permission
