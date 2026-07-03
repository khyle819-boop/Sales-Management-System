<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\DefectReport;
use App\Models\Installation;
use Illuminate\Http\Request;

class DefectReportController extends Controller
{
    public function store(Request $request, Installation $installation)
    {
        $this->authorizeInstallation($installation);
        $request->validate([
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high',
        ]);
        DefectReport::create([
            'installation_id' => $installation->id,
            'product_id' => $installation->product_id,
            'reported_by' => auth()->id(),
            'description' => $request->description,
            'severity' => $request->severity,
        ]);
        return back()->with('success','Defect reported.');
    }

    private function authorizeInstallation(Installation $installation): void
    {
        abort_if($installation->assigned_to !== auth()->id(), 403);
    }
}


