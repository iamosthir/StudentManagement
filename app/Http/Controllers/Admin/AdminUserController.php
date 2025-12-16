<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        // If not an AJAX request, return the SPA view
        if (!$request->expectsJson() && !$request->ajax()) {
            return view('admin.app');
        }

        $perPage = $request->input('per_page', 15);

        $admins = Admin::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($admins);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(StoreAdminUserRequest $request)
    {
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active ?? true,
        ]);

        // Assign roles
        if ($request->roles) {
            $admin->assignRole($request->roles);
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin user created successfully',
            'admin' => $admin->load('roles'),
        ], 201);
    }

    /**
     * Display the specified admin user.
     */
    public function show(Request $request, $id)
    {
        // If not an AJAX request, return the SPA view
        if (!$request->expectsJson() && !$request->ajax()) {
            return view('admin.app');
        }

        $admin = Admin::with('roles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'admin' => $admin,
        ]);
    }

    /**
     * Update the specified admin user.
     */
    public function update(UpdateAdminUserRequest $request, $id)
    {
        $admin = Admin::findOrFail($id);

        // Prevent editing own account's active status or roles through this endpoint
        if ($admin->id === auth('admin')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot edit your own account through this interface',
            ], 403);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active ?? $admin->is_active,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        // Sync roles
        if ($request->has('roles')) {
            $admin->syncRoles($request->roles);
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin user updated successfully',
            'admin' => $admin->load('roles'),
        ]);
    }

    /**
     * Remove the specified admin user (soft delete).
     */
    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);

        // Prevent deleting own account
        if ($admin->id === auth('admin')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        // Hard delete (you can change to soft delete if needed)
        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin user deleted successfully',
        ]);
    }

    /**
     * Get all available roles.
     */
    public function getRoles(Request $request)
    {
        // If not an AJAX request, return the SPA view
        if (!$request->expectsJson() && !$request->ajax()) {
            return view('admin.app');
        }

        $roles = Role::where('guard_name', 'admin')->get();

        return response()->json([
            'success' => true,
            'roles' => $roles,
        ]);
    }
}
