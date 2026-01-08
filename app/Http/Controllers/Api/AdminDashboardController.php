<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\{Order, Quote, User};
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        try {
            $totalUsers = User::where('role', 'user')->count();
            $totalQuotes = Quote::count();
            $totalRevenue = Order::where('status', 'completed')->sum('total_amount');

            return response()->json([
                'status' => true,
                'message' => 'Dashboard stats fetched successfully',
                'data' => [
                    'total_users' => $totalUsers,
                    'total_quotes' => $totalQuotes,
                    'total_revenue' => number_format($totalRevenue, 2, '.', ''),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getChartData(Request $request)
    {
        $request->validate([
            'type' => 'required|in:weekly,monthly',
        ]);

        try {
            $chartData = [];

            // Debug: Check raw data
            $debugInfo = [
                'total_orders' => Order::count(),
                'completed_orders' => Order::where('status', 'completed')->count(),
                'completed_with_amount' => Order::where('status', 'completed')
                    ->whereNotNull('total_amount')
                    ->where('total_amount', '>', 0)
                    ->count(),
                'sample_order' => Order::where('status', 'completed')
                    ->select('id', 'orderid', 'total_amount', 'created_at', 'status')
                    ->first(),
            ];

            if ($request->type === 'weekly') {
                $chartData = $this->getWeeklyData();
            } elseif ($request->type === 'monthly') {
                $chartData = $this->getMonthlyData();
            }

            // Calculate total for verification
            $total = collect($chartData)->sum('value');

            return response()->json([
                'status' => true,
                'message' => ucfirst($request->type).' chart data fetched successfully',
                'data' => [
                    'chart' => $chartData,
                    'total' => $total,
                    'currency' => 'USD',
                ],
                'debug' => $debugInfo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch chart data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // --- Private Helper: Weekly Logic (with Zero-Filling) ---
    private function getWeeklyData()
    {
        // 1. Get raw data - only completed orders with total_amount
        $rawWeeklyData = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('status', 'completed')
            ->whereNotNull('total_amount')
            ->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Format & Fill Zeros
        $weeklyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dateString = $dateObj->format('Y-m-d');
            $dayName = $dateObj->format('l');

            $dayData = $rawWeeklyData->firstWhere('date', $dateString);

            $weeklyChart[] = [
                'label' => $dayName, // Generic 'label' key for frontend
                'value' => $dayData ? (float) $dayData->total : 0.0,
            ];
        }

        return $weeklyChart;
    }

    // --- Private Helper: Monthly Logic ---
    private function getMonthlyData()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $endOfMonth = Carbon::now()->endOfMonth();

        // A. Get raw data for current month only - completed orders with total_amount
        $rawMonthlyData = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('status', 'completed')
            ->whereNotNull('total_amount')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // B. Loop from 1st of month until End of month
        $monthlyChart = [];

        // We clone the start date so we don't modify the original variable inside the loop
        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {
            $dateString = $currentDate->format('Y-m-d'); // "2025-12-01"

            // Find data for this date or return 0
            $dayData = $rawMonthlyData->firstWhere('date', $dateString);

            $monthlyChart[] = [
                'label' => $dateString, // Label is the Date (e.g. 2025-12-01)
                'value' => $dayData ? (float) $dayData->total : 0.0,
            ];

            // Move to next day
            $currentDate->addDay();
        }

        return $monthlyChart;
    }
}
