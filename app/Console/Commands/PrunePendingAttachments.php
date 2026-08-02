<?php

namespace App\Console\Commands;

use App\Services\AttachmentService;
use Illuminate\Console\Command;

/**
 * Removes screenshots uploaded during conversations that were never confirmed.
 *
 * Without this, an abandoned conversation leaves its files on disk forever.
 */
class PrunePendingAttachments extends Command
{
    protected $signature = 'shoprelle:prune-pending-attachments {--hours=24 : Age above which a staging directory is discarded}';

    protected $description = 'Delete screenshots staged by conversations that were never confirmed';

    public function handle(AttachmentService $attachments): int
    {
        $hours = (int) $this->option('hours');
        $removed = $attachments->prunePending($hours);

        $this->components->info(sprintf(
            '%d conversation(s) abandonnée(s) nettoyée(s) (plus de %d h).',
            $removed,
            $hours,
        ));

        return self::SUCCESS;
    }
}
