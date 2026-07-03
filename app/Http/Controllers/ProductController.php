<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::withCount(['sales as sales' => function($query) {
                $query->where('type', 'Product');
            }])
            ->withCount(['sales as service' => function($query) {
                $query->where('type', 'Service');
            }])
            ->where('status', 'active')
            ->get();

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'material' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:2048'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads'), $imageName);
            $data['img'] = 'uploads/' . $imageName;
        }

        if ($request->hasFile('document')) {
            $document = $request->file('document');
            $documentName = time() . '_' . $document->getClientOriginalName();
            $document->move(public_path('uploads'), $documentName);
            $data['document'] = 'uploads/' . $documentName;
        }

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Always load notes so admins can also see engineer notes
        $relations = ['sales.customer', 'notes.user'];
        $product = Product::with($relations)->findOrFail($id);
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Product::findOrFail($id);
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'material' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:2048'
        ]);

        $data = $request->all();
        
        if ($request->hasFile('img')) {
            // Delete old image if exists
            if ($product->img && file_exists(public_path($product->img))) {
                unlink(public_path($product->img));
            }
            
            $image = $request->file('img');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads'), $imageName);
            $data['img'] = 'uploads/' . $imageName;
        }

        if ($request->hasFile('document')) {
            // Delete old document if exists
            if ($product->document && file_exists(public_path($product->document))) {
                unlink(public_path($product->document));
            }
            
            $document = $request->file('document');
            $documentName = time() . '_' . $document->getClientOriginalName();
            $document->move(public_path('uploads'), $documentName);
            $data['document'] = 'uploads/' . $documentName;
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => 'inactive']);

        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }
}
