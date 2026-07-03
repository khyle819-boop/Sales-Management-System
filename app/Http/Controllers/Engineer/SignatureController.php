<?php

namespace App\Http\Controllers\Engineer;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use App\Models\Signature;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function store(Request $request, Installation $installation)
    {
        $this->authorizeInstallation($installation);
        $request->validate([
            'client_name' => 'nullable|string',
            'signature' => 'required|string' // base64 data URL
        ]);

        $data = $request->input('signature');
        if (preg_match('/^data:image\/(png|jpg|jpeg);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            $filename = 'signatures/'.time().'_'.uniqid().'.'.$type;
            \Storage::disk('public')->put($filename, $data);
        } else {
            return back()->with('error','Invalid signature format.');
        }

        // Check if signature already exists for this installation
        $existingSignature = Signature::where('installation_id', $installation->id)->first();
        
        if ($existingSignature) {
            // Update existing signature
            // Delete old image file
            if (\Storage::disk('public')->exists($existingSignature->image_path)) {
                \Storage::disk('public')->delete($existingSignature->image_path);
            }
            
            $existingSignature->update([
                'client_name' => $request->input('client_name'),
                'image_path' => $filename,
            ]);
            
            $message = 'Signature updated successfully.';
        } else {
            // Create new signature
            Signature::create([
                'installation_id' => $installation->id,
                'client_name' => $request->input('client_name'),
                'image_path' => $filename,
            ]);
            
            $message = 'Signature saved successfully.';
        }

        return back()->with('success', $message);
    }

    private function authorizeInstallation(Installation $installation): void
    {
        abort_if($installation->assigned_to !== auth()->id(), 403);
    }
}



