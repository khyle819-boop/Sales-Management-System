<?php
namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use App\Models\InstallationTask;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    public function index()
    {
        $installations = Installation::with(['customer','product','attachments'])
            ->where('assigned_to', auth()->id())
            ->withCount(['attachments', 'productNotes', 'defectReports', 'feedbacks'])
            ->latest()
            ->paginate(15);
        return view('engineer.installations.index', compact('installations'));
    }

    public function saveAll(Request $request, Installation $installation)
    {
        $this->authorizeView($installation);
        // Implement your save logic here, or just return success for now
        return back()->with('success', 'All changes saved.');
    }

    public function create()
    {
        abort(403);
    }

    public function store(Request $request) { abort(403); }

    public function show(Installation $installation)
    {
        $this->authorizeView($installation);
        $installation->load(['customer','product','tasks','attachments','productNotes.user','defectReports.reporter','feedbacks.user','signature','sale']);
        return view('engineer.installations.show', compact('installation'));
    }

    public function updateStatus(Request $request, Installation $installation)
    {
        $this->authorizeView($installation);
        $request->validate(['status' => 'required|in:pending,in_progress,completed']);
        $installation->update(['status' => $request->status]);
        // If completed and linked to a sale, mark sale as Done
        if ($installation->status === 'completed' && $installation->sale) {
            $installation->sale->update(['status' => 'Done']);
        }
        return back()->with('success','Status updated.');
    }

    public function addTask(Request $request, Installation $installation)
    {
        $this->authorizeView($installation);
        $request->validate(['title' => 'required|string']);
        InstallationTask::create([
            'installation_id' => $installation->id,
            'title' => $request->title,
            'sort_order' => ($installation->tasks()->max('sort_order') ?? 0) + 1,
        ]);
        return back()->with('success','Task added.');
    }

    public function toggleTask(InstallationTask $task)
    {
        $this->authorizeView($task->installation);
        $task->update(['is_done' => !$task->is_done]);
        return back()->with('success','Task updated.');
    }

    private function authorizeView(Installation $installation): void
    {
        abort_if($installation->assigned_to !== auth()->id(), 403);
    }
}


