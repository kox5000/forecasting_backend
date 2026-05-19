<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function totalSales()
    {
        $userId = auth()->id();
        $total = $this->dashboardService->getTotalSales($userId);
        return response()->json(['total_sales' => $total]);
    }

    public function salesOverTime()
    {
        $userId = auth()->id();
        $data = $this->dashboardService->getSalesOverTime($userId);
        return response()->json(['sales_over_time' => $data]);
    }

    public function topProducts()
    {
        $userId = auth()->id();
        $data = $this->dashboardService->getTopProducts($userId);
        return response()->json(['top_products' => $data]);
    }

    public function monthlyComparison()
    {
        $userId = auth()->id();
        $data = $this->dashboardService->getMonthlyComparison($userId);
        return response()->json(['monthly_comparison' => $data]);
    }

    public function salesGrowth()
    {
        $userId = auth()->id();
        $data = $this->dashboardService->getSalesGrowthData($userId);
        return response()->json($data);
    }

    public function movingAverage(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:3|max:30'
        ]);

        $userId = auth()->id();
        $days = $request->get('days', 7);
        $data = $this->dashboardService->getMovingAverage($userId, $days);
        return response()->json(['moving_average' => $data]);
    }

    public function trends()
    {
        $userId = auth()->id();
        $data = $this->dashboardService->detectTrends($userId);
        return response()->json(['trends' => $data]);
    }

    public function anomalies()
    {
        $userId = auth()->id();
        $data = $this->dashboardService->detectAnomalies($userId);
        return response()->json(['anomalies' => $data]);
    }
}
