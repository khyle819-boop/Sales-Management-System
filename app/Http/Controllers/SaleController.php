<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = Sale::with(['customer', 'product'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        return view('sales.create', compact('customers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'type' => 'required|in:Product,Service',
            'proof' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'delivery_receipt' => 'nullable|file|mimes:pdf,doc,docx|max:2048'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            $data['proof'] = 'uploads/' . $fileName;
        }

        if ($request->hasFile('delivery_receipt')) {
            $file = $request->file('delivery_receipt');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            $data['delivery_receipt'] = 'uploads/' . $fileName;
        }

        $sale = Sale::create($data);

        // Auto-create an installation for service jobs
        try {
            $assigneeId = optional(User::where('role','engineer')->first())->id; // simple assignment policy
            if ($assigneeId) {
                Installation::create([
                    'customer_id' => $sale->customer_id,
                    'product_id' => $sale->product_id,
                    'sale_id' => $sale->id,
                    'assigned_to' => $assigneeId,
                    'title' => 'Installation for Sale #'.$sale->id,
                    'location' => $sale->customer->address ?? null,
                    'due_date' => now()->addDays(3),
                    'status' => 'pending',
                    'notes' => 'Auto-created from sale record on '.now()->toDateTimeString(),
                ]);
            }
        } catch (\Throwable $e) {
            // swallow silently; admin can assign later
        }

        return redirect()->route('sales.index')->with('success', 'Sale created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::with(['customer', 'product'])->findOrFail($id);
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sale = Sale::findOrFail($id);
        $customers = Customer::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();
        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sale = Sale::findOrFail($id);
        
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'type' => 'required|in:Product,Service',
            'status' => 'required|in:Done,Pending',
            'proof' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'delivery_receipt' => 'nullable|file|mimes:pdf,doc,docx|max:2048'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('proof')) {
            // Delete old file if exists
            if ($sale->proof && file_exists(public_path($sale->proof))) {
                unlink(public_path($sale->proof));
            }
            
            $file = $request->file('proof');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            $data['proof'] = 'uploads/' . $fileName;
        }

        if ($request->hasFile('delivery_receipt')) {
            // Delete old file if exists
            if ($sale->delivery_receipt && file_exists(public_path($sale->delivery_receipt))) {
                unlink(public_path($sale->delivery_receipt));
            }
            
            $file = $request->file('delivery_receipt');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            $data['delivery_receipt'] = 'uploads/' . $fileName;
        }

        $sale->update($data);

        return redirect()->route('sales.index')->with('success', 'Sale updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sale = Sale::findOrFail($id);
        
        // Delete proof file if exists
        if ($sale->proof && file_exists(public_path($sale->proof))) {
            unlink(public_path($sale->proof));
        }
        
        // Delete delivery receipt file if exists
        if ($sale->delivery_receipt && file_exists(public_path($sale->delivery_receipt))) {
            unlink(public_path($sale->delivery_receipt));
        }
        
        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully!');
    }

    /**
     * Show proof of sale
     */
    public function showProof(string $id)
    {
        $sale = Sale::with(['customer', 'product'])->findOrFail($id);
        
        if (!$sale->proof || !file_exists(public_path($sale->proof))) {
            return redirect()->route('sales.index')->with('error', 'Proof file not found!');
        }

        return view('sales.proof', compact('sale'));
    }
}
