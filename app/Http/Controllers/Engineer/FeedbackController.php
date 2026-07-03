<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use App\Models\ClientFeedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, Installation $installation)
    {
        $this->authorizeInstallation($installation);
        $request->validate(['feedback' => 'required|string']);

        ClientFeedback::create([
            'installation_id' => $installation->id,
            'customer_id' => $installation->customer_id,
            'user_id' => auth()->id(),
            'feedback' => $request->feedback,
        ]);

        return back()->with('success','Client feedback recorded.');
    }

    private function authorizeInstallation(Installation $installation): void
    {
        abort_if($installation->assigned_to !== auth()->id(), 403);
    }
}



