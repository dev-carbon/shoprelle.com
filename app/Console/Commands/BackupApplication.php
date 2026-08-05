<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;

/**
 * Nightly safety net: the database and the customers' screenshots both live on
 * a single VPS disk, and this archive is what makes a mistake recoverable.
 */
class BackupApplication extends Command
{
    protected $signature = 'shoprelle:backup {--keep=14 : Nombre d\'archives conservées, la nouvelle comprise}';

    protected $description = 'Archive the database and customer screenshots, then rotate old backups';

    public function handle(BackupService $backups): int
    {
        $archivePath = $backups->run();
        $pruned = $backups->prune((int) $this->option('keep'));

        $this->components->info(sprintf(
            'Sauvegarde écrite dans %s (%s), %d ancienne(s) archive(s) supprimée(s).',
            $archivePath,
            Number::fileSize(File::size($archivePath)),
            $pruned,
        ));

        return self::SUCCESS;
    }
}
