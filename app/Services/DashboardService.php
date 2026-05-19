<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected int $cacheTtl = 3600; // 1 hour

    public function getTotalSales(int $userId): float
    {
        return Cache::remember("dashboard.total_sales.{$userId}", $this->cacheTtl, function () use ($userId) {
            return Sale::whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->sum('revenue');
        });
    }

    public function getSalesOverTime(int $userId): array
    {
        return Cache::remember("dashboard.sales_over_time.v2.{$userId}", $this->cacheTtl, function () use ($userId) {
            $driver = DB::getDriverName();
            $yearExpression = $driver === 'sqlite' ? "strftime('%Y', date)" : 'YEAR(date)';
            $monthExpression = $driver === 'sqlite' ? "strftime('%m', date)" : 'MONTH(date)';
            $monthLabelExpression = $driver === 'sqlite' ? "strftime('%Y-%m', date)" : "DATE_FORMAT(date, '%Y-%m')";

            $rows = Sale::select(
                DB::raw("{$yearExpression} as year"),
                DB::raw("{$monthExpression} as month"),
                DB::raw('SUM(revenue) as total_revenue')
            )
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->groupBy('year', 'month')
            ->orderByRaw("{$yearExpression} desc, {$monthExpression} desc")
            ->limit(6)
            ->get()
            ->toArray();

            $rows = array_reverse($rows);

            return array_map(function ($row) {
                return [
                    'month' => sprintf('%04d-%02d', $row['year'], $row['month']),
                    'total_revenue' => (float) $row['total_revenue'],
                ];
            }, $rows);
        });
    }

    public function getTopProducts(int $userId, int $limit = 5): array
    {
        return Cache::remember("dashboard.top_products.{$userId}.{$limit}", $this->cacheTtl, function () use ($userId, $limit) {
            return Sale::select(
                'products.name',
                DB::raw('SUM(sales.revenue) as total_revenue')
            )
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->where('products.user_id', $userId)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
        });
    }

    public function getMonthlyComparison(int $userId): array
    {
        return Cache::remember("dashboard.monthly_comparison.{$userId}", $this->cacheTtl, function () use ($userId) {
            $driver = DB::getDriverName();
            $yearExpression = $driver === 'sqlite' ? "strftime('%Y', date)" : 'YEAR(date)';
            $monthExpression = $driver === 'sqlite' ? "strftime('%m', date)" : 'MONTH(date)';

            return Sale::select(
                DB::raw("{$yearExpression} as year"),
                DB::raw("{$monthExpression} as month"),
                DB::raw('SUM(revenue) as total_revenue')
            )
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->toArray();
        });
    }

    public function getSalesGrowthData(int $userId): array
    {
        return Cache::remember("dashboard.sales_growth_data.{$userId}", $this->cacheTtl, function () use ($userId) {
            $currentMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            $currentSales = Sale::whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('date', [$currentMonth, now()])
            ->sum('revenue');

            $lastMonthSales = Sale::whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('date', [$lastMonth, $currentMonth->copy()->subDay()])
            ->sum('revenue');

            $growth = round($currentSales - $lastMonthSales, 2);

            if ($lastMonthSales == 0) {
                $percentage = $currentSales > 0 ? 100.0 : 0.0;
            } else {
                $percentage = round((($currentSales - $lastMonthSales) / $lastMonthSales) * 100, 2);
            }

            return [
                'growth' => $growth,
                'percentage' => $percentage,
            ];
        });
    }

    public function getMovingAverage(int $userId, int $days = 7): array
    {
        return Cache::remember("dashboard.moving_average.{$userId}.{$days}", $this->cacheTtl, function () use ($userId, $days) {
            $sales = $this->getSalesOverTime($userId);
            $movingAverage = [];

            foreach ($sales as $index => $sale) {
                $window = array_slice($sales, max(0, $index - $days + 1), $days);
                $average = 0;

                if (!empty($window)) {
                    $average = array_sum(array_column($window, 'total_revenue')) / count($window);
                }

                $movingAverage[] = [
                    'date' => $sale['date'],
                    'moving_average' => round($average, 2),
                ];
            }

            return $movingAverage;
        });
    }

    public function detectTrends(int $userId): array
    {
        return Cache::remember("dashboard.trends.{$userId}", $this->cacheTtl, function () use ($userId) {
            $sales = $this->getSalesOverTime($userId);

            if (count($sales) < 2) {
                return ['trend' => 'insufficient_data'];
            }

            $recent = array_slice($sales, -7); // Last 7 days
            $previous = array_slice($sales, -14, 7); // Previous 7 days

            $recentAvg = array_sum(array_column($recent, 'total_revenue')) / count($recent);
            $previousAvg = count($previous) ? array_sum(array_column($previous, 'total_revenue')) / count($previous) : 0;

            if ($previousAvg == 0) {
                return [
                    'trend' => $recentAvg > 0 ? 'increasing' : 'stable',
                    'change_percentage' => $recentAvg > 0 ? 100.0 : 0.0,
                ];
            }

            $change = (($recentAvg - $previousAvg) / $previousAvg) * 100;

            if ($change > 10) {
                return ['trend' => 'increasing', 'change_percentage' => round($change, 2)];
            } elseif ($change < -10) {
                return ['trend' => 'decreasing', 'change_percentage' => round($change, 2)];
            }

            return ['trend' => 'stable', 'change_percentage' => round($change, 2)];
        });
    }

    public function detectAnomalies(int $userId): array
    {
        return Cache::remember("dashboard.anomalies.{$userId}", $this->cacheTtl, function () use ($userId) {
            $sales = $this->getSalesOverTime($userId);

            if (count($sales) < 10) {
                return ['anomalies' => []];
            }

            $revenues = array_column($sales, 'total_revenue');
            $mean = array_sum($revenues) / count($revenues);
            $variance = 0;

            foreach ($revenues as $revenue) {
                $variance += pow($revenue - $mean, 2);
            }
            $variance /= count($revenues);
            $stdDev = sqrt($variance);

            if ($stdDev == 0) {
                return ['anomalies' => []];
            }

            $anomalies = [];
            foreach ($sales as $sale) {
                $zScore = abs($sale['total_revenue'] - $mean) / $stdDev;
                if ($zScore > 2.5) { // 2.5 standard deviations
                    $anomalies[] = [
                        'date' => $sale['date'],
                        'revenue' => $sale['total_revenue'],
                        'z_score' => round($zScore, 2),
                        'type' => $sale['total_revenue'] > $mean ? 'high' : 'low'
                    ];
                }
            }

            return ['anomalies' => $anomalies];
        });
    }

    public function clearUserCache(int $userId): void
    {
        Cache::forget("dashboard.total_sales.{$userId}");
        Cache::forget("dashboard.sales_over_time.{$userId}");
        Cache::forget("dashboard.sales_over_time.v2.{$userId}");
        Cache::forget("dashboard.monthly_comparison.{$userId}");
        Cache::forget("dashboard.sales_growth.{$userId}");
        Cache::forget("dashboard.trends.{$userId}");
        Cache::forget("dashboard.anomalies.{$userId}");

        // Clear top products cache (multiple limits possible)
        for ($i = 1; $i <= 10; $i++) {
            Cache::forget("dashboard.top_products.{$userId}.{$i}");
        }

        for ($days = 3; $days <= 30; $days++) {
            Cache::forget("dashboard.moving_average.{$userId}.{$days}");
        }
    }
}