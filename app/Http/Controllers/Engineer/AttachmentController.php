<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\EngineerAttachment;
use App\Models\Installation;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function store(Request $request, Installation $installation)
    {
        $this->authorizeInstallation($installation);
        $request->validate([
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:4096',
            'description' => 'nullable|string'
        ]);

        $uploadedFiles = [];
        $files = $request->file('files');
        
        if ($files) {
            foreach ($files as $file) {
                $filename = time().'_'.uniqid().'_'.$file->getClientOriginalName();
                $path = $file->storeAs('engineer_attachments', $filename, 'public');

                $attachment = EngineerAttachment::create([
                    'installation_id' => $installation->id,
                    'product_id' => $installation->product_id,
                    'path' => $path,
                    'type' => $file->getClientMimeType(),
                    'description' => $request->description,
                ]);
                
                $uploadedFiles[] = $attachment;
            }
        }

        $count = count($uploadedFiles);
        return back()->with('success', $count > 1 ? "{$count} files uploaded successfully." : "File uploaded successfully.");
    }

    private function authorizeInstallation(Installation $installation): void
    {
        abort_if($installation->assigned_to !== auth()->id(), 403);
    }
}



