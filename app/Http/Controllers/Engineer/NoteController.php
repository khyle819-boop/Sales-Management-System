<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductNote;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'installation_id' => 'required|exists:installations,id',
            'product_id' => 'required|exists:products,id',
            'note' => 'required|string',
        ]);

        $note = new ProductNote();
        $note->installation_id = $request->installation_id;
        $note->product_id = $request->product_id;
        $note->note = $request->note;
        $note->user_id = auth()->id();
        $note->save();

        return back()->with('success', 'Note added successfully.');
    }
    public function storeForProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'note' => 'required|string',
        ]);

        $note = new ProductNote();
        $note->product_id = $request->product_id;
        $note->note = $request->note;
        $note->user_id = auth()->id();
        $note->save();

        return back()->with('success', 'Product note added successfully.');
    }
}
