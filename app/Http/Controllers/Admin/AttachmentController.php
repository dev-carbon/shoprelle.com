<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Stream a customer screenshot from the private disk.
     *
     * The attachment is resolved through its request so a valid id cannot be
     * used to read a file belonging to another request.
     */
    public function show(PurchaseRequest $purchaseRequest, Attachment $attachment): StreamedResponse
    {
        $this->authorize('viewAttachments', $purchaseRequest);

        abort_unless($attachment->purchase_request_id === $purchaseRequest->id, 404);
        abort_unless($attachment->fileExists(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                // Never let the browser sniff or execute a customer upload.
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ],
        );
    }
}
