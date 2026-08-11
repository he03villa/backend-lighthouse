<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EvidenceService
{
    public function storeFile(UploadedFile $file, string $tenantId): string
    {
        $path = $file->store('evidences/'.$tenantId, 'public');

        return Storage::disk('public')->url($path);
    }
}
