<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function index(Request $request): JsonResponse
    {
        // Get current month and last month dates
        $currentMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Total Students
        $totalStudents = Student::count();
        $lastMonthStudents = Student::where('created_at', '>=', $lastMonthStart)
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();
        $studentsGrowth = $lastMonthStudents > 0
            ? round((($totalStudents - $lastMonthStudents) / $lastMonthStudents) * 100, 1)
            : 0;

        // Active Subscriptions
        $activeSubscriptions = StudentSubscription::where('expiry_date', '>=', now())
            ->orWhereNull('expiry_date')
            ->count();
        $lastMonthSubscriptions = StudentSubscription::where('created_at', '>=', $lastMonthStart)
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();
        $subscriptionsGrowth = $lastMonthSubscriptions > 0
            ? round((($activeSubscriptions - $lastMonthSubscriptions) / $lastMonthSubscriptions) * 100, 1)
            : 0;

        // Monthly Revenue
        $monthlyRevenue = Payment::where('status', 'paid')
            ->where('created_at', '>=', $currentMonthStart)
            ->sum('amount');
        $lastMonthRevenue = Payment::where('status', 'paid')
            ->where('created_at', '>=', $lastMonthStart)
            ->where('created_at', '<=', $lastMonthEnd)
            ->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Pending Payments
        $pendingPayments = Payment::where('status', 'pending')->count();
        $lastMonthPending = Payment::where('status', 'pending')
            ->where('created_at', '>=', $lastMonthStart)
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();
        $pendingGrowth = $lastMonthPending > 0
            ? round((($pendingPayments - $lastMonthPending) / $lastMonthPending) * 100, 1)
            : 0;

        // Recent Students (last 5)
        $recentStudents = Student::with('program')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'admission_number' => $student->admission_number,
                    'full_name' => $student->full_name,
                    'email' => $student->email,
                    'program' => $student->program ? $student->program->name : 'N/A',
                    'status' => $student->status,
                    'created_at' => $student->created_at->format('Y-m-d'),
                ];
            });

        // Recent Activity
        $recentActivity = $this->getRecentActivity();

        // Revenue Chart Data (last 7 days)
        $revenueChartData = $this->getRevenueChartData();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => [
                    'total_students' => [
                        'value' => $totalStudents,
                        'growth' => $studentsGrowth,
                        'trend' => $studentsGrowth >= 0 ? 'up' : 'down',
                    ],
                    'active_subscriptions' => [
                        'value' => $activeSubscriptions,
                        'growth' => $subscriptionsGrowth,
                        'trend' => $subscriptionsGrowth >= 0 ? 'up' : 'down',
                    ],
                    'monthly_revenue' => [
                        'value' => (float) $monthlyRevenue,
                        'growth' => $revenueGrowth,
                        'trend' => $revenueGrowth >= 0 ? 'up' : 'down',
                    ],
                    'pending_payments' => [
                        'value' => $pendingPayments,
                        'growth' => abs($pendingGrowth),
                        'trend' => $pendingGrowth <= 0 ? 'down' : 'up', // Down is good for pending
                    ],
                ],
                'recent_students' => $recentStudents,
                'recent_activity' => $recentActivity,
                'chart_data' => $revenueChartData,
            ],
        ]);
    }

    /**
     * Get revenue chart data for the last 7 days.
     */
    private function getRevenueChartData(): array
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $revenue = Payment::where('status', 'paid')
                ->whereDate('created_at', $date->toDateString())
                ->sum('amount');

            $data[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get recent activity.
     */
    private function getRecentActivity(): array
    {
        $activities = [];

        // Recent students
        $newStudents = Student::latest()->take(2)->get();
        foreach ($newStudents as $student) {
            $activities[] = [
                'type' => 'student_registered',
                'icon' => 'person-plus-fill',
                'icon_class' => 'success',
                'text' => "New student registered: {$student->full_name}",
                'time' => $student->created_at->diffForHumans(),
                'timestamp' => $student->created_at->timestamp,
            ];
        }

        // Recent payments
        $recentPayments = Payment::where('status', 'paid')
            ->with('student')
            ->latest()
            ->take(2)
            ->get();
        foreach ($recentPayments as $payment) {
            $activities[] = [
                'type' => 'payment_received',
                'icon' => 'cash',
                'icon_class' => 'info',
                'text' => "Payment received - $" . number_format($payment->amount, 2),
                'time' => $payment->created_at->diffForHumans(),
                'timestamp' => $payment->created_at->timestamp,
            ];
        }

        // Expiring subscriptions
        $expiringSubscriptions = StudentSubscription::with('student')
            ->whereBetween('expiry_date', [now(), now()->addDays(7)])
            ->take(2)
            ->get();
        foreach ($expiringSubscriptions as $subscription) {
            $activities[] = [
                'type' => 'subscription_expiring',
                'icon' => 'exclamation-circle',
                'icon_class' => 'warning',
                'text' => "Subscription expiring soon: {$subscription->student->full_name}",
                'time' => $subscription->expiry_date->diffForHumans(),
                'timestamp' => $subscription->expiry_date->timestamp,
            ];
        }

        // Sort by timestamp descending
        usort($activities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        // Return only the first 4
        return array_slice($activities, 0, 4);
    }
}
