<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentAuthController extends Controller
{
    /**
     * Register a new student account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'admission_number' => 'required|string|unique:students,admission_number',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:students,email',
            'password' => 'required|string|min:6|confirmed',
            'birthdate' => 'nullable|date',
            'guardian_name' => 'nullable|string|max:255',
        ]);

        $student = Student::create($validated);

        $token = $student->createToken('StudentApp')->accessToken;

        return response()->json([
            'message' => 'Student registered successfully',
            'student' => [
                'id' => $student->id,
                'admission_number' => $student->admission_number,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'email' => $student->email,
                'status' => $student->status,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login a student with phone/email and password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // Can be phone or email
            'password' => 'required|string',
        ]);

        // Try to find student by phone or email
        $student = Student::where('phone', $request->login)
            ->orWhere('email', $request->login)
            ->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if student is active
        if (!$student->isActive()) {
            return response()->json([
                'message' => 'Your account is not active. Please contact administration.',
                'status' => $student->status,
            ], 403);
        }

        // Create token
        $token = $student->createToken('StudentApp')->accessToken;

        return response()->json([
            'message' => 'Login successful',
            'student' => [
                'id' => $student->id,
                'admission_number' => $student->admission_number,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'email' => $student->email,
                'status' => $student->status,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get the authenticated student's information.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $student = $request->user();

        return response()->json([
            'student' => [
                'id' => $student->id,
                'admission_number' => $student->admission_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'phone' => $student->phone,
                'email' => $student->email,
                'birthdate' => $student->birthdate?->format('Y-m-d'),
                'guardian_name' => $student->guardian_name,
                'status' => $student->status,
                'created_at' => $student->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Logout the authenticated student.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
