<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle admin login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Attempt to authenticate using the admin guard
        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $admin = Auth::guard('admin')->user();

            // Check if admin is active
            if (!$admin->is_active) {
                Auth::guard('admin')->logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => '/admin/dashboard',
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'roles' => $admin->getRoleNames()->values()->toArray(),
                    'permissions' => $admin->getAllPermissions()->pluck('name')->values()->toArray(),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'The provided credentials do not match our records.',
            'errors' => [
                'email' => ['The provided credentials are incorrect.'],
            ],
        ], 422);
    }

    /**
     * Handle admin logout request.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/admin/login',
        ]);
    }

    /**
     * Get authenticated admin information.
     */
    public function me(Request $request)
    {
        // If not an AJAX request, return the SPA view
        if (!$request->expectsJson() && !$request->ajax()) {
            return view('admin.app');
        }

        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        // Get the admin's primary wallet balance
        $primaryWallet = $admin->primaryWallet();
        $balance = $primaryWallet ? $primaryWallet->balance : 0;

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => $admin->is_active,
                'balance' => $balance,
                'roles' => $admin->getRoleNames()->values()->toArray(),
                'permissions' => $admin->getAllPermissions()->pluck('name')->values()->toArray(),
            ],
        ]);
    }
}
