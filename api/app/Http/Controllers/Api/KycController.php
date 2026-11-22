<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KycDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KycController extends Controller
{
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doc_type' => 'required|string|in:passport,drivers_license,national_id',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();

        if ($user->kyc_status === 'approved') {
            return response()->json(['message' => 'KYC already approved'], 400);
        }

        $file = $request->file('document');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('kyc', $filename, 'local');

        $document = KycDocument::create([
            'user_id' => $user->id,
            'doc_type' => $validated['doc_type'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        $user->update(['kyc_status' => 'pending']);

        AuditLog::log('kyc.submitted', $user->id, 'KycDocument', $document->id);

        return response()->json([
            'message' => 'KYC document submitted successfully',
            'document' => $document,
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $documents = $user->kycDocuments()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'kyc_status' => $user->kyc_status,
            'documents' => $documents,
        ]);
    }
}
