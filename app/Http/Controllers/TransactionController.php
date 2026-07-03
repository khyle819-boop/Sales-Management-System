<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['customer', 'product'])
            ->latest()
            ->paginate(15);
        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::where('status', 'active')->get();
        return view('transactions.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'required|in:inquiry,order,payment',
            'description' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'due_date' => 'nullable|date|after:today'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('payment_proofs', $filename, 'public');
            $data['payment_proof'] = $filename;
        }

        $data['reference_number'] = 'TXN-' . date('Y') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT);

        Transaction::create($data);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction created successfully!');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['customer', 'product']);
        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $customers = Customer::all();
        $products = Product::where('status', 'active')->get();
        $transaction->load(['customer', 'product']);
        return view('transactions.edit', compact('transaction', 'customers', 'products'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'required|in:inquiry,order,payment',
            'description' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'due_date' => 'nullable|date'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('payment_proof')) {
            // Delete old file if exists
            if ($transaction->payment_proof) {
                Storage::disk('public')->delete('payment_proofs/' . $transaction->payment_proof);
            }
            
            $file = $request->file('payment_proof');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('payment_proofs', $filename, 'public');
            $data['payment_proof'] = $filename;
        }

        $transaction->update($data);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaction updated successfully!');
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->payment_proof) {
            Storage::disk('public')->delete('payment_proofs/' . $transaction->payment_proof);
        }
        
        $transaction->delete();
        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully!');
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $transaction->update(['status' => $request->status]);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaction status updated successfully!');
    }

    public function inquiries()
    {
        $inquiries = Transaction::with(['customer', 'product'])
            ->where('type', 'inquiry')
            ->latest()
            ->paginate(15);
        return view('transactions.inquiries', compact('inquiries'));
    }

    public function orders()
    {
        $orders = Transaction::with(['customer', 'product'])
            ->where('type', 'order')
            ->latest()
            ->paginate(15);
        return view('transactions.orders', compact('orders'));
    }

    public function payments()
    {
        $payments = Transaction::with(['customer', 'product'])
            ->where('type', 'payment')
            ->latest()
            ->paginate(15);
        return view('transactions.payments', compact('payments'));
    }
}
