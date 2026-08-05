<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Archives everything the application could not recreate: the SQLite database
 * and the screenshots customers sent with their requests.
 *
 * Each run produces one self-contained zip whose name carries its timestamp,
 * so the archives sort — and rotate — by filename alone. The database goes
 * through `VACUUM INTO`, SQLite's supported way of snapshotting a database
 * that is being written to, rather than a raw copy of a possibly mid-write
 * file.
 */
class BackupService
{
    /**
     * Writes one archive and returns its absolute path.
     */
    public function run(): string
    {
        $directory = config('shoprelle.backups.directory');
        File::ensureDirectoryExists($directory);

        $archivePath = $directory.'/shoprelle-'.now()->format('Y-m-d-His').'.zip';
        $databaseSnapshot = $this->snapshotDatabase($directory);

        try {
            $zip = new ZipArchive;

            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Impossible de créer l'archive [{$archivePath}].");
            }

            $zip->addFile($databaseSnapshot, 'database.sqlite');
            $this->addAttachments($zip);
            $zip->close();
        } finally {
            File::delete($databaseSnapshot);
        }

        return $archivePath;
    }

    /**
     * Deletes the oldest archives past the given count and returns how many
     * were removed.
     */
    public function prune(int $keep): int
    {
        return collect(File::files(config('shoprelle.backups.directory')))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn ($file): string => $file->getFilename())
            ->slice(max($keep, 0))
            ->each(fn ($file) => File::delete($file->getPathname()))
            ->count();
    }

    /**
     * Snapshots the live database into a temporary file inside the backup
     * directory, so the final rename into the zip never crosses filesystems.
     */
    private function snapshotDatabase(string $directory): string
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Seule une base SQLite peut être sauvegardée par cette commande.');
        }

        // VACUUM INTO refuses to overwrite its target; the random name
        // guarantees a fresh one even if a previous run died mid-way.
        $snapshotPath = $directory.'/database-'.Str::uuid().'.sqlite';

        $connection->statement(sprintf(
            "VACUUM INTO '%s'",
            str_replace("'", "''", $snapshotPath),
        ));

        return $snapshotPath;
    }

    private function addAttachments(ZipArchive $zip): void
    {
        $disk = Storage::disk(config('shoprelle.attachments.disk'));

        foreach ($disk->allFiles(config('shoprelle.attachments.directory')) as $file) {
            $zip->addFile($disk->path($file), 'attachments/'.$file);
        }
    }
}
