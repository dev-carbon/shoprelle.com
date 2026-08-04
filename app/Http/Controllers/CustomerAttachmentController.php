<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\CustomerAccessService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rendre au client les captures qu'il nous a envoyées.
 *
 * Elles étaient visibles du back-office et de personne d'autre — pas même de
 * celui qui les a prises. Or c'est lui qui a le plus besoin de les revoir :
 * c'est ainsi qu'il vérifie qu'il a joint la bonne photo au bon produit.
 *
 * L'autorisation ne tient pas au fichier mais au chemin d'accès : la référence
 * doit appartenir au client identifié en session — le même couple numéro + code
 * que « Mes demandes » — et la pièce jointe doit appartenir à cette demande.
 * Un identifiant valable seul n'ouvre donc rien.
 */
class CustomerAttachmentController extends Controller
{
    public function __construct(private readonly CustomerAccessService $access) {}

    public function show(string $reference, Attachment $attachment): StreamedResponse
    {
        $customer = $this->access->identified();

        abort_if($customer === null, 403);

        $request = $customer->purchaseRequests()
            ->where('reference', $reference)
            ->first();

        abort_if($request === null, 404);
        abort_unless($attachment->purchase_request_id === $request->id, 404);
        abort_unless($attachment->fileExists(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                // Jamais de reniflage ni d'exécution sur un fichier déposé par
                // le public, même quand c'est son propre auteur qui le relit.
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ],
        );
    }
}
