<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Services\ErrorLogService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le journal des erreurs, lu depuis le fichier de log de l'application.
 *
 * Il n'y a pas de service de suivi d'erreurs externe : quand un email
 * d'alerte arrive, c'est ici qu'on vient voir le détail.
 */
class ErrorLogController extends Controller
{
    public function __invoke(ErrorLogService $journal): Response
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        return Inertia::render('admin/logs/index', [
            'entries' => $journal->latest(),
        ]);
    }
}
