<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $query = Installation::where('assigned_to', auth()->id())
            ->whereBetween('due_date', [$from, $to]);

        $total = (clone $query)->count();
        $completed = (clone $query)->where('status','completed')->count();
        $inProgress = (clone $query)->where('status','in_progress')->count();
        $pending = (clone $query)->where('status','pending')->count();

        return view('engineer.reports.summary', compact('from','to','total','completed','inProgress','pending'));
    }
}


