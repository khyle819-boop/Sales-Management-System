<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Get date range for filtering
        if ($startDate && $endDate) {
            $query = Sale::query()->whereBetween('date', [$startDate, $endDate]);
        } else {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $query = Sale::query()->where('date', '>=', $thirtyDaysAgo);
        }

        // Sales Analytics
        $salesAnalytics = $this->getSalesAnalytics($startDate, $endDate);
        
        // Product Performance Analytics
        $productAnalytics = $this->getProductAnalytics($startDate, $endDate);
        
        // Customer Analytics
        $customerAnalytics = $this->getCustomerAnalytics($startDate, $endDate);
        
        // Revenue Analytics
        $revenueAnalytics = $this->getRevenueAnalytics($startDate, $endDate);
        
        // Performance Trends
        $performanceTrends = $this->getPerformanceTrends($startDate, $endDate);

        return view('analytics.index', compact(
            'salesAnalytics',
            'productAnalytics', 
            'customerAnalytics',
            'revenueAnalytics',
            'performanceTrends',
            'startDate',
            'endDate'
        ));
    }

    private function getSalesAnalytics($startDate = null, $endDate = null)
    {
        $baseQuery = Sale::query();
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('date', [$startDate, $endDate]);
        } else {
            $baseQuery->where('date', '>=', Carbon::now()->subDays(30));
        }

        // Clone the base query for each metric to avoid stacking where clauses
        $totalSales   = (clone $baseQuery)->count();
        $completedSales = (clone $baseQuery)->where('status', 'Done')->count();
        $pendingSales   = (clone $baseQuery)->where('status', 'Pending')->count();
        $productSales   = (clone $baseQuery)->where('type', 'Product')->count();
        $serviceSales   = (clone $baseQuery)->where('type', 'Service')->count();

        // Daily sales trend for the last 30 days (SQLite compatible)
        $dailyTrend = Sale::select(DB::raw('date as sale_date'), DB::raw('COUNT(*) as total_sales'))
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        // Monthly comparison
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        $currentMonthSales = Sale::whereBetween('date', [$currentMonth, Carbon::now()])->count();
        $lastMonthSales = Sale::whereBetween('date', [$lastMonth, $currentMonth])->count();
        
        $growthRate = $lastMonthSales > 0 ? (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100 : 0;

        return [
            'total_sales' => $totalSales,
            'completed_sales' => $completedSales,
            'pending_sales' => $pendingSales,
            'product_sales' => $productSales,
            'service_sales' => $serviceSales,
            'completion_rate' => $totalSales > 0 ? round(($completedSales / $totalSales) * 100, 1) : 0,
            'daily_trend' => $dailyTrend,
            'current_month_sales' => $currentMonthSales,
            'last_month_sales' => $lastMonthSales,
            'growth_rate' => round($growthRate, 1)
        ];
    }

    private function getProductAnalytics($startDate = null, $endDate = null)
    {
        // Top performing products
        $topProducts = Sale::select('product_id', DB::raw('COUNT(*) as total_sales'))
            ->with('product')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('product_id')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        // Product category performance
        $productCategories = Product::select('category', DB::raw('COUNT(*) as total_products'))
            ->groupBy('category')
            ->get();

        // Low performing products (no sales in last 30 days)
        $lowPerformingProducts = Product::whereDoesntHave('sales', function($query) {
                $query->where('date', '>=', Carbon::now()->subDays(30));
            })
            ->where('status', 'active')
            ->limit(5)
            ->get();

        return [
            'top_products' => $topProducts,
            'product_categories' => $productCategories,
            'low_performing_products' => $lowPerformingProducts
        ];
    }

    private function getCustomerAnalytics($startDate = null, $endDate = null)
    {
        // Top customers by purchase frequency
        $topCustomers = Sale::select('customer_id', DB::raw('COUNT(*) as total_purchases'))
            ->with('customer')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('customer_id')
            ->orderBy('total_purchases', 'desc')
            ->limit(10)
            ->get();

        // Customer retention analysis
        $newCustomers = Customer::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::whereHas('sales', function($query) {
                $query->where('date', '>=', Carbon::now()->subDays(30));
            })->count();

        // Customer lifetime value
        $customerLifetimeValue = Sale::select('customer_id', DB::raw('COUNT(*) as total_orders'))
            ->with('customer')
            ->groupBy('customer_id')
            ->orderBy('total_orders', 'desc')
            ->limit(5)
            ->get();

        return [
            'top_customers' => $topCustomers,
            'new_customers' => $newCustomers,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'customer_retention_rate' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0,
            'customer_lifetime_value' => $customerLifetimeValue
        ];
    }

    private function getRevenueAnalytics($startDate = null, $endDate = null)
    {
        $query = Sale::with('product');
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            $query->where('date', '>=', Carbon::now()->subDays(30));
        }

        // Get sales with product data
        $sales = $query->get();
        
        // Calculate total revenue using product prices
        $totalRevenue = $sales->sum(function($sale) {
            return $sale->product ? $sale->product->price : 0;
        });
        
        // Revenue by type
        $productRevenue = $sales->where('type', 'Product')->sum(function($sale) {
            return $sale->product ? $sale->product->price : 0;
        });
        
        $serviceRevenue = $sales->where('type', 'Service')->sum(function($sale) {
            return $sale->product ? $sale->product->price : 0;
        });

        // Average order value
        $totalOrders = $sales->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Revenue trend by date
        $revenueTrend = $sales->groupBy(function($sale) {
            return $sale->date->format('Y-m-d');
        })->map(function($daySales) {
            return $daySales->sum(function($sale) {
                return $sale->product ? $sale->product->price : 0;
            });
        })->map(function($revenue, $date) {
            return [
                'sale_date' => $date,
                'daily_revenue' => $revenue
            ];
        })->values();

        return [
            'total_revenue' => $totalRevenue,
            'product_revenue' => $productRevenue,
            'service_revenue' => $serviceRevenue,
            'average_order_value' => round($averageOrderValue, 2),
            'revenue_trend' => $revenueTrend
        ];
    }

    private function getPerformanceTrends($startDate = null, $endDate = null)
    {
        // Sales performance by day of week (SQLite compatible)
        $salesByDayOfWeek = Sale::select(DB::raw('strftime("%w", date) as day_of_week'), DB::raw('COUNT(*) as total_sales'))
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        // Sales performance by hour (SQLite compatible)
        $salesByHour = Sale::select(DB::raw('strftime("%H", created_at) as hour'), DB::raw('COUNT(*) as total_sales'))
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Conversion rate (completed vs pending)
        $totalSales = Sale::when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })->count();
        
        $completedSales = Sale::where('status', 'Done')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })->count();

        $conversionRate = $totalSales > 0 ? ($completedSales / $totalSales) * 100 : 0;

        return [
            'sales_by_day_of_week' => $salesByDayOfWeek,
            'sales_by_hour' => $salesByHour,
            'conversion_rate' => round($conversionRate, 1)
        ];
    }
} 