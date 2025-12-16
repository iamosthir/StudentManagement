# Admin Users Management Module

## Overview
This module allows Administrators to manage admin users and their roles in the system.

## Backend Implementation

### Controller
**Location:** `app/Http/Controllers/Admin/AdminUserController.php`

**Methods:**
- `index()` - List all admin users with pagination and search
- `store()` - Create a new admin user
- `show($id)` - Show a single admin user
- `update($id)` - Update an admin user
- `destroy($id)` - Delete an admin user (hard delete)
- `getRoles()` - Get all available roles for dropdowns

**Security Features:**
- Prevents users from editing/deleting their own account
- Only users with "Administrator" role can access these endpoints

### Form Requests
**Location:** `app/Http/Requests/Admin/`

1. **StoreAdminUserRequest.php**
   - Validates name, email (unique), password (min 8 chars, confirmed), roles
   - Authorizes only "Administrator" role

2. **UpdateAdminUserRequest.php**
   - Same as store but password is optional
   - Email uniqueness check excludes current user

### Routes
**Location:** `routes/web.php`

Protected by `auth:admin` and `role:Administrator` middleware:
- `GET /admin/admin-users` - List admins
- `POST /admin/admin-users` - Create admin
- `GET /admin/admin-users/{id}` - Show admin
- `PUT /admin/admin-users/{id}` - Update admin
- `DELETE /admin/admin-users/{id}` - Delete admin
- `GET /admin/roles` - List roles

## Frontend Implementation

### Vue Components
**Location:** `resources/js/admin/views/AdminUsers/`

1. **AdminUserList.vue**
   - Table view with search functionality
   - Columns: Name, Email, Roles, Status, Actions
   - Features:
     - Pagination
     - Delete confirmation modal
     - Search with debounce
     - RTL layout with glass effect design

2. **AdminUserForm.vue**
   - Shared component for Create/Edit
   - Fields:
     - Name (required)
     - Email (required)
     - Password (required for create, optional for edit)
     - Password Confirmation
     - Roles (checkboxes)
     - Status (active/inactive toggle)
   - Features:
     - Form validation with error display
     - Auto-detect edit mode from route params

### Vue Router
**Location:** `resources/js/admin/router/index.js`

Routes added:
- `/admin/admin-users` → `admin.users.index`
- `/admin/admin-users/create` → `admin.users.create`
- `/admin/admin-users/:id/edit` → `admin.users.edit`

All routes protected with `requiresAuth: true` meta.

### Sidebar Menu
**Location:** `resources/js/admin/components/layout/AdminSidebar.vue`

Added "Admin Users" link in the System section:
- Icon: `bi-person-badge`
- Route: `admin.users.index`
- Active state detection for all admin.users.* routes

## Testing Instructions

### 1. Run Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```

This will create:
- 3 roles: Administrator, Accountant, Registrar
- 3 test admin users with different roles

### 2. Test Admin Users
**Administrator:**
- Email: admin@example.com
- Password: password
- Has access to Admin Users module

**Accountant:**
- Email: accountant@example.com
- Password: password
- No access to Admin Users module (will see 403 error)

**Registrar:**
- Email: registrar@example.com
- Password: password
- No access to Admin Users module (will see 403 error)

### 3. Test Workflow

1. **Login** as admin@example.com
2. **Navigate** to Admin Users from sidebar
3. **Search** for users by name or email
4. **Create** a new admin user:
   - Fill in all required fields
   - Select one or more roles
   - Set status to Active
   - Submit form
5. **Edit** an existing user:
   - Click edit icon
   - Modify fields (password is optional)
   - Save changes
6. **Delete** a user:
   - Click delete icon
   - Confirm deletion in modal
7. **Test restrictions**:
   - Try to edit your own account (should show error)
   - Try to delete your own account (should show error)

### 4. API Testing with Postman/Insomnia

**Get all admin users:**
```http
GET /admin/admin-users
Headers:
  Accept: application/json
  Cookie: laravel_session=...
```

**Create admin user:**
```http
POST /admin/admin-users
Headers:
  Accept: application/json
  Content-Type: application/json
  Cookie: laravel_session=...
Body:
{
  "name": "Test Admin",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "roles": ["Accountant"],
  "is_active": true
}
```

**Update admin user:**
```http
PUT /admin/admin-users/4
Headers:
  Accept: application/json
  Content-Type: application/json
  Cookie: laravel_session=...
Body:
{
  "name": "Updated Name",
  "email": "updated@example.com",
  "roles": ["Registrar"],
  "is_active": true
}
```

**Delete admin user:**
```http
DELETE /admin/admin-users/4
Headers:
  Accept: application/json
  Cookie: laravel_session=...
```

## UI Features

### Design
- Glass morphism effect with white theme
- Gradient buttons and badges
- Smooth animations and transitions
- Fully responsive (mobile-friendly)
- RTL layout support

### User Experience
- Real-time search with debounce
- Inline validation errors
- Loading states for async operations
- Confirmation modal for destructive actions
- Breadcrumb navigation with back button

## Security Notes

1. **Authorization:**
   - Only "Administrator" role can access admin user management
   - Users cannot edit/delete their own accounts
   - Backend enforces authorization through Form Requests

2. **Validation:**
   - Email uniqueness checked
   - Password must be min 8 characters
   - Password confirmation required
   - Roles must exist in database

3. **Delete Behavior:**
   - Currently using **hard delete** (permanent)
   - To use soft delete:
     - Add `use SoftDeletes;` to Admin model
     - Add `deleted_at` column to migration
     - Change `$admin->delete()` to soft delete in controller

## Future Enhancements

- [ ] Add soft delete with restore functionality
- [ ] Add bulk actions (delete multiple users)
- [ ] Add export to CSV/Excel
- [ ] Add activity log for admin actions
- [ ] Add email notifications when admin accounts are created
- [ ] Add profile picture upload
- [ ] Add two-factor authentication option
- [ ] Add password reset functionality
