<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->input('report_type', 'sales');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $data = [];
        $columns = [];
        $analytics = [];

        if ($reportType === 'sales') {
            $query = Sale::with(['customer', 'product']);
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }
            $data = $query->get();
            $columns = ['Order Number', 'Customer Name', 'Product Name', 'Date', 'Type', 'Status'];
            $analytics = $this->getSalesAnalytics($startDate, $endDate);
        } elseif ($reportType === 'products') {
            $query = Product::withCount(['sales as sales_count' => function($query) {
                    $query->where('type', 'Product');
                }])
                ->withCount(['sales as service_count' => function($query) {
                    $query->where('type', 'Service');
                }]);
            $data = $query->get();
            $columns = ['Product Name', 'Total Sales', 'Total Service', 'Price', 'Status'];
            $analytics = $this->getProductAnalytics($startDate, $endDate);
        } elseif ($reportType === 'customers') {
            $query = Customer::query();
            $data = $query->get();
            $columns = ['Customer Name', 'Email', 'Phone', 'Status'];
            $analytics = $this->getCustomerAnalytics($startDate, $endDate);
        } elseif ($reportType === 'invoices') {
            $query = \App\Models\Invoice::with(['customer', 'sale.product']);
            if ($startDate && $endDate) {
                $query->whereBetween('due_date', [$startDate, $endDate]);
            }
            $data = $query->get();
            $columns = ['Invoice #', 'Customer', 'Product', 'Total', 'Status', 'Due Date'];
            $analytics = $this->getInvoiceAnalytics($startDate, $endDate);
        }

        return view('reports', compact('data', 'columns', 'reportType', 'startDate', 'endDate', 'analytics'));
    }

    private function getSalesAnalytics($startDate = null, $endDate = null)
    {
        $query = Sale::query();
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            $query->where('date', '>=', Carbon::now()->subDays(30));
        }

        $totalSales = $query->count();
        $completedSales = $query->where('status', 'Done')->count();
        $pendingSales = $query->where('status', 'Pending')->count();
        $productSales = $query->where('type', 'Product')->count();
        $serviceSales = $query->where('type', 'Service')->count();

        // Top performing products
        $topProducts = Sale::select('product_id', DB::raw('count(*) as total_sales'))
            ->with('product')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('product_id')
            ->orderBy('total_sales', 'desc')
            ->limit(5)
            ->get();

        // Top customers
        $topCustomers = Sale::select('customer_id', DB::raw('count(*) as total_purchases'))
            ->with('customer')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('customer_id')
            ->orderBy('total_purchases', 'desc')
            ->limit(5)
            ->get();

        // Monthly trend
        $monthlyTrend = Sale::select(DB::raw('strftime("%Y-%m", date) as month'), DB::raw('count(*) as total'))
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        // Suggested actions
        $suggestions = [];
        
        if ($pendingSales > $completedSales * 0.3) {
            $suggestions[] = "High number of pending sales ({$pendingSales}). Consider following up with customers to complete transactions.";
        }
        
        if ($serviceSales < $productSales * 0.2) {
            $suggestions[] = "Service sales are low compared to product sales. Consider promoting service offerings.";
        }
        
        if ($topProducts->isNotEmpty()) {
            $topProduct = $topProducts->first();
            $suggestions[] = "Top performing product: {$topProduct->product->name} ({$topProduct->total_sales} sales). Consider increasing stock or promoting similar products.";
        }

        return [
            'total_sales' => $totalSales,
            'completed_sales' => $completedSales,
            'pending_sales' => $pendingSales,
            'product_sales' => $productSales,
            'service_sales' => $serviceSales,
            'completion_rate' => $totalSales > 0 ? round(($completedSales / $totalSales) * 100, 1) : 0,
            'top_products' => $topProducts,
            'top_customers' => $topCustomers,
            'monthly_trend' => $monthlyTrend,
            'suggestions' => $suggestions
        ];
    }

    private function getProductAnalytics($startDate = null, $endDate = null)
    {
        $query = Product::withCount(['sales as sales_count' => function($query) use ($startDate, $endDate) {
                $query->where('type', 'Product');
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            }])
            ->withCount(['sales as service_count' => function($query) use ($startDate, $endDate) {
                $query->where('type', 'Service');
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            }]);

        $products = $query->get();
        
        $totalProducts = $products->count();
        $activeProducts = $products->where('status', 'active')->count();
        $topPerforming = $products->sortByDesc('sales_count')->take(3);
        $lowPerforming = $products->where('sales_count', 0)->take(3);

        $suggestions = [];
        
        if ($lowPerforming->isNotEmpty()) {
            $suggestions[] = "Found {$lowPerforming->count()} products with no sales. Consider promotional campaigns or review pricing.";
        }
        
        if ($topPerforming->isNotEmpty()) {
            $topProduct = $topPerforming->first();
            $suggestions[] = "Best performing product: {$topProduct->name} ({$topProduct->sales_count} sales). Consider increasing inventory.";
        }

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'top_performing' => $topPerforming,
            'low_performing' => $lowPerforming,
            'suggestions' => $suggestions
        ];
    }

    private function getCustomerAnalytics($startDate = null, $endDate = null)
    {
        $query = Customer::withCount(['sales as total_purchases' => function($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            }]);

        $customers = $query->get();
        
        $totalCustomers = $customers->count();
        $activeCustomers = $customers->where('status', 'active')->count();
        $topCustomers = $customers->sortByDesc('total_purchases')->take(5);
        $inactiveCustomers = $customers->where('total_purchases', 0)->take(5);

        $suggestions = [];
        
        if ($inactiveCustomers->isNotEmpty()) {
            $suggestions[] = "Found {$inactiveCustomers->count()} customers with no purchases. Consider re-engagement campaigns.";
        }
        
        if ($topCustomers->isNotEmpty()) {
            $topCustomer = $topCustomers->first();
            $suggestions[] = "Top customer: {$topCustomer->name} ({$topCustomer->total_purchases} purchases). Consider loyalty rewards.";
        }

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'top_customers' => $topCustomers,
            'inactive_customers' => $inactiveCustomers,
            'suggestions' => $suggestions
        ];
    }

    private function getInvoiceAnalytics($startDate = null, $endDate = null)
    {
        $query = \App\Models\Invoice::query();
        if ($startDate && $endDate) {
            $query->whereBetween('due_date', [$startDate, $endDate]);
        } else {
            $query->where('due_date', '>=', Carbon::now()->subDays(30));
        }

        $totalInvoices = $query->count();
        $paidInvoices = $query->where('status', 'paid')->count();
        $pendingInvoices = $query->whereIn('status', ['draft', 'sent'])->count();
        $overdueInvoices = $query->where('status', 'overdue')->count();

        // Top customers by invoice count
        $topCustomers = \App\Models\Invoice::select('customer_id', DB::raw('count(*) as total_invoices'))
            ->with('customer')
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('due_date', [$startDate, $endDate]);
            })
            ->groupBy('customer_id')
            ->orderBy('total_invoices', 'desc')
            ->limit(5)
            ->get();

        // Monthly trend
        $monthlyTrend = \App\Models\Invoice::select(DB::raw('strftime("%Y-%m", due_date) as month'), DB::raw('count(*) as total'))
            ->when($startDate && $endDate, function($q) use ($startDate, $endDate) {
                return $q->whereBetween('due_date', [$startDate, $endDate]);
            })
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        // Suggested actions
        $suggestions = [];
        if ($overdueInvoices > 0) {
            $suggestions[] = "Follow up on {$overdueInvoices} overdue invoices";
        }
        if ($pendingInvoices > 0) {
            $suggestions[] = "Send {$pendingInvoices} pending invoices to customers";
        }

        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'pending_invoices' => $pendingInvoices,
            'overdue_invoices' => $overdueInvoices,
            'top_customers' => $topCustomers,
            'monthly_trend' => $monthlyTrend,
            'suggestions' => $suggestions
        ];
    }

    public function exportExcel(Request $request)
    {
        $reportType = $request->input('report_type', 'sales');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $data = [];
        $columns = [];

        if ($reportType === 'sales') {
            $query = Sale::with(['customer', 'product']);
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }
            $data = $query->get();
            $columns = ['Order Number', 'Customer Name', 'Product Name', 'Date', 'Type', 'Status'];
        } elseif ($reportType === 'products') {
            $query = Product::withCount(['sales as sales_count' => function($query) {
                    $query->where('type', 'Product');
                }])
                ->withCount(['sales as service_count' => function($query) {
                    $query->where('type', 'Service');
                }]);
            $data = $query->get();
            $columns = ['Product Name', 'Total Sales', 'Total Service', 'Price', 'Status'];
        } elseif ($reportType === 'customers') {
            $query = Customer::query();
            $data = $query->get();
            $columns = ['Customer Name', 'Email', 'Phone', 'Status'];
        } elseif ($reportType === 'invoices') {
            $query = \App\Models\Invoice::with(['customer', 'sale.product']);
            if ($startDate && $endDate) {
                $query->whereBetween('due_date', [$startDate, $endDate]);
            }
            $data = $query->get();
            $columns = ['Invoice #', 'Customer', 'Product', 'Total', 'Status', 'Due Date'];
        }

        $filename = $reportType . '_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $handle = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($handle, $columns);
        
        // Add data
        foreach ($data as $row) {
            $csvRow = [];
            if ($reportType === 'sales') {
                $csvRow = [
                    $row->id,
                    $row->customer->name ?? '',
                    $row->product->name ?? '',
                    $row->date ? $row->date->format('M d, Y') : '',
                    $row->type,
                    $row->status
                ];
            } elseif ($reportType === 'products') {
                $csvRow = [
                    $row->name,
                    $row->sales_count ?? 0,
                    $row->service_count ?? 0,
                    $row->price,
                    $row->status
                ];
            } elseif ($reportType === 'customers') {
                $csvRow = [
                    $row->name,
                    $row->email,
                    $row->phone,
                    $row->status
                ];
            } elseif ($reportType === 'invoices') {
                $csvRow = [
                    $row->invoice_number,
                    $row->customer->name ?? '',
                    $row->sale->product->name ?? '',
                    $row->total,
                    $row->status,
                    $row->due_date ? $row->due_date->format('M d, Y') : ''
                ];
            }
            fputcsv($handle, $csvRow);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $reportType = $request->input('report_type', 'sales');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $data = [];
        $columns = [];

        if ($reportType === 'sales') {
            $query = Sale::with(['customer', 'product']);
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }
            $data = $query->get();
            $columns = ['Order Number', 'Customer Name', 'Product Name', 'Date', 'Type', 'Status'];
        } elseif ($reportType === 'products') {
            $query = Product::withCount(['sales as sales_count' => function($query) {
                    $query->where('type', 'Product');
                }])
                ->withCount(['sales as service_count' => function($query) {
                    $query->where('type', 'Service');
                }]);
            $data = $query->get();
            $columns = ['Product Name', 'Total Sales', 'Total Service', 'Price', 'Status'];
        } elseif ($reportType === 'customers') {
            $query = Customer::query();
            $data = $query->get();
            $columns = ['Customer Name', 'Email', 'Phone', 'Status'];
        }

        $html = view('reports.pdf', compact('data', 'columns', 'reportType', 'startDate', 'endDate'))->render();
        
        $filename = $reportType . '_report_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // For now, return HTML that can be printed to PDF
        return Response::make($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}