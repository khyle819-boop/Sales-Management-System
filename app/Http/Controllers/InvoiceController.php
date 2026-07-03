<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['customer', 'sale'])->latest()->paginate(10);
        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $sales = Sale::with(['customer', 'product'])->whereDoesntHave('invoice')->get();
        $selectedSaleId = $request->get('sale_id');
        return view('invoices.create', compact('sales', 'selectedSaleId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string'
        ]);

        $sale = Sale::with(['customer', 'product'])->findOrFail($request->sale_id);
        
        // Calculate tax based on sale type
        $taxRate = $sale->type === 'Service' ? 0.03 : 0.12; // 3% for service, 12% for sales
        $subtotal = $sale->product->price;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;
        
        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . date('Y') . '-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'notes' => $request->notes,
            'due_date' => $request->due_date,
            'status' => 'draft'
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully!');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'sale.product']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['customer', 'sale.product']);
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue'
        ]);

        // Recalculate tax based on sale type
        $sale = $invoice->sale;
        $taxRate = $sale->type === 'Service' ? 0.03 : 0.12; // 3% for service, 12% for sales
        $tax = $invoice->subtotal * $taxRate;
        $total = $invoice->subtotal + $tax;

        $invoice->update([
            'tax' => $tax,
            'total' => $total,
            'notes' => $request->notes,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'paid_date' => $request->status === 'paid' ? now() : null
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully!');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    public function download(Invoice $invoice)
    {
        $invoice->load(['customer', 'sale.product']);
        
    $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function email(Invoice $invoice)
    {
        $invoice->load(['customer', 'sale.product']);
        
        // Update status to sent
        $invoice->update(['status' => 'sent']);
        
        // Send email (you'll need to configure your mail settings)
        // Mail::to($invoice->customer->email)->send(new InvoiceEmail($invoice));
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice sent via email successfully!');
    }

    public function markAsPaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_date' => now()
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice marked as paid!');
    }
}
