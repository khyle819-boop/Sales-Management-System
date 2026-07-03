<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Installation;

class InstallationController extends Controller
{
    public function index()
    {
        // Fetch all installations for admin
        $installations = Installation::with(['customer', 'product'])->get();
        return view('admin.installations', compact('installations'));
    }
    public function show(Installation $installation)
    {
        $installation->load(['customer', 'product', 'defectReports', 'feedbacks', 'signature', 'attachments', 'assignee']);
        return view('admin.installations.show', compact('installation'));
    }
}
