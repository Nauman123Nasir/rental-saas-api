<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get aggregated KPI numbers and chart trend data for dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // KPI Counters
        $totalAssets = Asset::where('tenant_id', $tenantId)->count();
        $availableAssets = Asset::where('tenant_id', $tenantId)->where('status', 'Available')->count();
        $activeRentals = Rental::where('tenant_id', $tenantId)->where('status', 'Active')->count();
        
        $todayRevenue = Payment::where('tenant_id', $tenantId)
            ->whereDate('payment_datetime_utc', now()->toDateString())
            ->sum('amount');

        $pendingReservations = Reservation::where('tenant_id', $tenantId)
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->count();

        // Status Distribution
        $statusDistribution = [
            'Available' => Asset::where('tenant_id', $tenantId)->where('status', 'Available')->count(),
            'Reserved'  => Asset::where('tenant_id', $tenantId)->where('status', 'Reserved')->count(),
            'Rented'    => Asset::where('tenant_id', $tenantId)->where('status', 'Rented')->count(),
            'Maintenance' => Asset::where('tenant_id', $tenantId)->where('status', 'Maintenance')->count(),
        ];

        // Generate past 6 months list (YYYY-MM)
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $revenueTrend = array_fill_keys($months, 0.0);
        $bookingsTrend = array_fill_keys($months, 0);

        // Fetch revenue trends
        $payments = Payment::where('tenant_id', $tenantId)
            ->where('payment_datetime_utc', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('DATE_FORMAT(payment_datetime_utc, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->get();

        foreach ($payments as $payment) {
            if (isset($revenueTrend[$payment->month])) {
                $revenueTrend[$payment->month] = round((float) $payment->total, 2);
            }
        }

        // Fetch bookings trends
        $reservations = Reservation::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->get();

        foreach ($reservations as $res) {
            if (isset($bookingsTrend[$res->month])) {
                $bookingsTrend[$res->month] = (int) $res->count;
            }
        }

        // Format charts for consumption
        $revenueChart = [];
        $bookingsChart = [];
        foreach ($months as $month) {
            $revenueChart[] = [
                'month' => $month,
                'total' => $revenueTrend[$month]
            ];
            $bookingsChart[] = [
                'month' => $month,
                'count' => $bookingsTrend[$month]
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_assets'         => $totalAssets,
                    'available_assets'     => $availableAssets,
                    'active_rentals'       => $activeRentals,
                    'today_revenue'        => round((float) $todayRevenue, 2),
                    'pending_reservations' => $pendingReservations,
                ],
                'status_distribution' => $statusDistribution,
                'trends' => [
                    'revenue'  => $revenueChart,
                    'bookings' => $bookingsChart
                ]
            ]
        ]);
    }
}
