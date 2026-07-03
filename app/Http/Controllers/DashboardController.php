<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Date range filter
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $query = Sale::query();
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $query->where('date', '>=', $thirtyDaysAgo);
        }
        $salesData = $query->selectRaw('
                COUNT(*) as total_sales,
                SUM(CASE WHEN type = "Product" THEN 1 ELSE 0 END) as product_sales,
                SUM(CASE WHEN type = "Service" THEN 1 ELSE 0 END) as service_sales,
                SUM(CASE WHEN status = "Done" THEN 1 ELSE 0 END) as completed_sales,
                SUM(CASE WHEN status != "Done" THEN 1 ELSE 0 END) as pending_sales
            ')
            ->first();

        // Most popular products (top 5) in date range
        $popularProducts = Product::withCount(['sales as sale_count' => function($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                } else {
                    $q->where('date', '>=', Carbon::now()->subDays(30));
                }
            }])
            ->where('status', 'active')
            ->orderBy('sale_count', 'desc')
            ->limit(5)
            ->get();

        // Prepare data for Chart.js
        $popularProductLabels = $popularProducts->pluck('name');
        $popularProductSales = $popularProducts->pluck('sale_count');

        // Top customers by sales (unchanged)
        $topCustomers = Customer::withCount(['sales as total_purchases' => function($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                } else {
                    $q->where('date', '>=', Carbon::now()->subDays(30));
                }
            }])
            ->where('status', 'active')
            ->orderBy('total_purchases', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('salesData', 'popularProducts', 'topCustomers')
            + [
                'popularProductLabels' => $popularProductLabels,
                'popularProductSales' => $popularProductSales
            ]);
    }
}
