# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Educational Center Student & Subscription Management System built with:

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 (Composition API) + Vue Router + Vite
- **UI Libraries**: Bootstrap 5 + PrimeVue (both with RTL support), Bootstrap Icons
- **Styling**: Tailwind CSS 4.0 + custom CSS
- **Authentication**:
  - Admin: Session-based (`admin` guard)
  - Student API: Token-based via Laravel Passport (`student` guard)
- **Authorization**: Spatie Laravel Permission package with roles (Administrator, Accountant, Registrar)
- **Testing**: Pest PHP

## Common Development Commands

### Initial Setup
```bash
composer setup  # Install dependencies, generate key, migrate, build frontend
```

### Development
```bash
composer dev    # Starts Laravel server, queue worker, and Vite dev server concurrently
# Or run individually:
php artisan serve
npm run dev
php artisan queue:listen
```

### Frontend
```bash
npm run dev     # Vite dev server with HMR
npm run build   # Production build
```

### Testing
```bash
composer test              # Run all tests
php artisan test           # Run Pest tests
php artisan test --filter TestName  # Run specific test
```

### Database
```bash
php artisan migrate
php artisan migrate:fresh --seed    # Fresh migration with seeders
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=AdminSeeder
```

### Code Quality
```bash
./vendor/bin/pint              # Laravel Pint (code formatter)
php artisan permission:cache-reset  # Clear permission cache
```

### Passport Setup
```bash
php artisan passport:install
php artisan passport:keys
```

## Architecture & Code Structure

### Backend Structure

**Authentication Guards** (defined in [config/auth.php](config/auth.php)):
- `web` - Default session guard for users table
- `admin` - Session guard for admins table
- `student` - Passport (API) guard for students table

**Routes Organization**:
- [routes/web.php](routes/web.php) - All admin routes (both SPA catchall and AJAX API endpoints)
  - Admin routes prefixed with `/admin`
  - Protected with `auth:admin` middleware
  - Role-based access using `role:RoleName` middleware
  - **IMPORTANT**: Admin API routes are in web.php (not api.php) because they use session auth
- [routes/api.php](routes/api.php) - Student API routes only
  - Student routes prefixed with `/student`
  - Protected with `auth:student` middleware (Passport)

**Models & Relationships**:
- Admin: Has roles and permissions via Spatie package
- Student: Authenticatable via Passport, has subscriptions, payments, attachments
- Program: Has many subscription options and products (many-to-many)
- StudentSubscription: Links students to programs via subscription options
- Payment: Has many payment items, linked to student
- Wallet: Staff, main cashbox, expense wallets with transactions
- Expense: Linked to expense categories and wallets

**Request Validation**:
- Use Form Request classes in `app/Http/Requests/`
- Separate namespaces: `App\Http\Requests\Admin\` for admin requests
- Authorization logic in Form Requests (e.g., `role:Administrator` checks)

**API Resources**:
- Located in `app/Http/Resources/`
- Used for consistent JSON responses
- Include relationships as needed

### Frontend Structure

**Admin SPA** ([resources/js/admin/](resources/js/admin/)):
```
admin/
├── app.js              # Vue app entry point
├── router/index.js     # Vue Router config with auth guards
├── layouts/
│   └── AdminLayout.vue # Main layout with sidebar + header
├── views/
│   ├── Auth/AdminLogin.vue
│   ├── Dashboard.vue
│   ├── AdminUsers/     # Admin user management
│   ├── Students/       # Student management
│   └── Payments/       # Payment management
    └── components/
    ├── layout/         # Header, Sidebar, Footer
    └── TableSkeleton.vue  # Reusable loading skeleton
```

**Router Configuration**:
- Uses `createWebHistory()` for clean URLs
- Navigation guard checks `localStorage` for optimistic auth
- `verifyAuth()` function for backend session verification
- Routes have `requiresAuth` or `requiresGuest` meta

**Axios Usage**:
- Import and call axios directly in components (no shared API service)
- Session-based auth means no manual token handling needed
- Admin auth state stored in localStorage: `admin_authenticated`, `admin_user`

**Path Alias**:
- `@` resolves to `resources/js/admin/` (configured in [vite.config.js](vite.config.js))
- Example: `import TableSkeleton from '@/components/TableSkeleton.vue'`

### CSS Structure

All CSS files in [resources/css/](resources/css/) are imported via [app.css](resources/css/app.css):
- `primevue-rtl.css` - PrimeVue RTL overrides
- `modern-theme.css` - Main theme with gradients and animations
- `user-list.css` - Admin user list styles
- `students.css` - Student management styles

**DO NOT** add CSS inside Vue components. Create separate CSS files and import them in app.css.

## Design System & UI Guidelines

### Theme Colors
- **Gradients**: Indigo → Purple → Pink (#6366f1 → #8b5cf6 → #ec4899)
- **Status**: Success #10b981, Warning #f59e0b, Danger #ef4444, Info #3b82f6
- **Text**: Primary #1e293b, Secondary #64748b, Muted #94a3b8
- **Base**: White #ffffff, Light Gray #f8fafc

### Design Features
- Clean white cards with subtle shadows
- Gradient text and buttons
- Magical transitions: `cubic-bezier(0.68, -0.55, 0.265, 1.55)`
- Border radius: 12px-20px
- Floating animations for icons
- Pulsing effects for badges

### Layout
- Sticky top navbar (white with gradient logo)
- Right-aligned sidebar (RTL support, white, no visible scrollbar)
- Collapsible sidebar on mobile
- Card-based content layout

### Loading States
**ALWAYS** use TableSkeleton.vue for data tables during API calls:
```vue
<TableSkeleton v-if="loading" :rows="5" :columns="5" />
<table v-else class="data-table">
  <!-- Your table content -->
</table>
```

### Language
- All UI text must be in **English** (translation will be done separately)
- Use clear, short labels

## Roles & Permissions

### Default Admin Accounts
All with password: `password`
- `admin@example.com` - Administrator (full access)
- `accountant@example.com` - Accountant (payments, wallets, expenses)
- `registrar@example.com` - Registrar (students, subscriptions)

### Permission Checking

**Backend**:
```php
// In controllers
if ($admin->can('create students')) { }

// In routes
Route::middleware(['auth:admin', 'role:Administrator'])->group(function () {});
Route::middleware(['auth:admin', 'permission:create students'])->group(function () {});
```

**Frontend**:
```vue
<script setup>
const adminUser = ref(JSON.parse(localStorage.getItem('admin_user')));
const canCreate = computed(() =>
  adminUser.value?.permissions?.includes('create students')
);
</script>
<template>
  <button v-if="canCreate">Add Student</button>
</template>
```

### Cache Management
After modifying roles/permissions:
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
// Or via artisan
php artisan permission:cache-reset
```

## Key Patterns & Conventions

### Vue Components
- Use Composition API with `<script setup>`
- Call axios directly (no shared API service layer)
- Store admin user in localStorage after login
- Use route meta for auth requirements

### Form Design Pattern
**IMPORTANT**: All forms MUST use PrimeVue components and follow the StudentForm design pattern.

**Reference**: [resources/js/admin/views/Students/StudentForm.vue](resources/js/admin/views/Students/StudentForm.vue)

**Required PrimeVue Components**:
- `InputText` - For text inputs
- `InputNumber` - For number inputs (prices, quantities)
- `Select` - For dropdown selections
- `MultiSelect` - For multiple selections
- `DatePicker` - For date inputs
- `Textarea` - For multi-line text
- `ToggleSwitch` - For boolean toggles
- `Button` - For all buttons
- `Password` - For password inputs

**Form Structure**:
1. **Page Header**: Title with gradient, subtitle, and "Back to List" button
2. **Form Sections**: Organize fields into logical sections with:
   - Section header with gradient icon
   - Section title and subtitle
   - Section body with form fields
3. **Field Structure**:
   - Label with icon (using Bootstrap Icons)
   - Required asterisk (*) if needed
   - PrimeVue input component
   - Error message display
   - Optional hint text
4. **Form Actions**: Cancel and Submit buttons at the bottom

**Styling Requirements**:
- Use scoped styles with gradient effects
- Hover animations on sections (scale, shadow)
- Floating icon animations
- Form sections should have staggered entrance animations
- Use the standard gradient: `linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)`
- Section icons with different colors: primary (blue/purple), success (green), warning (orange)

**Example Import Structure**:
```vue
<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
// ... other PrimeVue components
</script>
```

**DO NOT**:
- Use regular HTML form elements (`<input>`, `<select>`, etc.)
- Use Bootstrap form components
- Create forms without the section-based structure
- Skip the gradient styling and animations

### API Endpoints
- Admin endpoints return JSON with `Accept: application/json` header
- Form validation errors return 422 with error array
- Authorization failures return 403
- Use Resource classes for consistent responses

### File Uploads
- Student attachments stored via StudentAttachment model
- Attachment types: id_card, birth_certificate, photo, other

### Database Conventions
- Use migrations for schema changes
- Soft deletes where appropriate (check model traits)
- Foreign keys with cascades defined in migrations
- Seeders in `database/seeders/`

## Module Implementation Checklist

When building a new module:
1. Create migration(s) with relationships
2. Create model with relationships and fillable/casts
3. Create Form Request(s) for validation
4. Create Resource class(es) for JSON responses
5. Create controller with methods
6. Add routes to web.php (admin) or api.php (students)
7. Create Vue views in `resources/js/admin/views/`
8. Add routes to Vue Router
9. Create CSS file if needed and import in app.css
10. Update sidebar navigation if needed

## Important Files

- [composer.json](composer.json) - PHP dependencies and scripts
- [package.json](package.json) - NPM dependencies and scripts
- [vite.config.js](vite.config.js) - Vite configuration with Vue and Tailwind
- [config/auth.php](config/auth.php) - Authentication guards and providers
- [routes/web.php](routes/web.php) - Admin routes (both SPA and API)
- [routes/api.php](routes/api.php) - Student API routes
- [resources/js/admin/router/index.js](resources/js/admin/router/index.js) - Vue Router config
- [resources/views/admin/app.blade.php](resources/views/admin/app.blade.php) - Admin SPA mount point

## Additional Documentation

- [ROLES_PERMISSIONS.md](ROLES_PERMISSIONS.md) - Detailed roles and permissions guide
- [ADMIN_USERS_MODULE.md](ADMIN_USERS_MODULE.md) - Admin user management module documentation
