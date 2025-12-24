<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        try {
            $totalUsers = User::where('role', 'user')->count();
            $totalQuotes = Quote::count();
            $totalRevenue = Order::where('status', 'paid')->sum('total_amount');

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

            if ($request->type === 'weekly') {
                $chartData = $this->getWeeklyData();
            } elseif ($request->type === 'monthly') {
                $chartData = $this->getMonthlyData();
            }

            return response()->json([
                'status' => true,
                'message' => ucfirst($request->type).' chart data fetched successfully',
                'data' => $chartData,
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
        // 1. Get raw data
        $rawWeeklyData = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(6))
            ->groupBy('date')
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
                'value' => $dayData ? (float) $dayData->total : 0,
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

        // A. Get raw data for current month only
        $rawMonthlyData = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('status', 'paid')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->get();

        // B. Loop from 1st of month until Today
        $monthlyChart = [];

        // We clone the start date so we don't modify the original variable inside the loop
        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {
            $dateString = $currentDate->format('Y-m-d'); // "2025-12-01"

            // Find data for this date or return 0
            $dayData = $rawMonthlyData->firstWhere('date', $dateString);

            $monthlyChart[] = [
                'label' => $dateString, // Label is the Date (e.g. 2025-12-01)
                'value' => $dayData ? (float) $dayData->total : 0,
            ];

            // Move to next day
            $currentDate->addDay();
        }

        return $monthlyChart;
    }
}
