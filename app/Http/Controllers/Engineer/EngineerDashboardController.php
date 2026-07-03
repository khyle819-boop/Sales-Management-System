<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Carbon;

class EngineerDashboardController extends Controller
{
    public function index()
    {
        $allInstallations = \App\Models\Installation::with(['customer', 'product', 'attachments'])
            ->where('assigned_to', auth()->id())
            ->withCount(['attachments', 'productNotes', 'defectReports', 'feedbacks'])
            ->get();

        // Remove title 'a'
        $filtered = $allInstallations->filter(function($i) {
            return $i->title !== 'a';
        });

        // Sort so that Installation for Sale #5-9 go first in order, then the rest by number ascending
        $getNum = function($title) {
            if (preg_match('/#(\d+)/', $title, $m)) return (int)$m[1];
            return PHP_INT_MAX;
        };
        $priorityList = [5,6,7,8,9,10,11,12,13,14];
        $sorted = $filtered->sort(function($a, $b) use ($getNum, $priorityList) {
            $aNum = $getNum($a->title);
            $bNum = $getNum($b->title);
            $aPriority = in_array($aNum, $priorityList) ? array_search($aNum, $priorityList) : count($priorityList) + $aNum;
            $bPriority = in_array($bNum, $priorityList) ? array_search($bNum, $priorityList) : count($priorityList) + $bNum;
            return $aPriority <=> $bPriority;
        });

        // Paginate manually
        $perPage = 10;
        $page = request()->input('page', 1);
        $installations = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Dashboard cards
        $totalInstallations = $filtered->count();
        $dueToday = $filtered->filter(function($i) {
            return optional($i->due_date)->isToday();
        })->count();

        return view('engineer.dashboard', compact('totalInstallations', 'dueToday', 'installations'));
    }
}


