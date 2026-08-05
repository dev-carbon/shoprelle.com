<?php

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->backupDirectory = sys_get_temp_dir().'/shoprelle-backup-test-'.uniqid();
    config()->set('shoprelle.backups.directory', $this->backupDirectory);

    Storage::fake(config('shoprelle.attachments.disk'));

    // RefreshDatabase wraps every test in a transaction, and SQLite refuses to
    // VACUUM inside one. Committing here mirrors production, where the command
    // runs with no transaction open; the trait notices the missing transaction
    // on teardown and re-migrates the next test from scratch.
    DB::commit();
});

afterEach(function () {
    File::deleteDirectory($this->backupDirectory);
});

it('archives the database and the customer screenshots', function () {
    Customer::factory()->create();

    $capturePath = config('shoprelle.attachments.directory').'/REQ-2026-0001/capture.png';
    Storage::disk(config('shoprelle.attachments.disk'))->put($capturePath, 'fake-png-bytes');

    $this->artisan('shoprelle:backup')->assertSuccessful();

    $archives = File::files($this->backupDirectory);
    expect($archives)->toHaveCount(1);

    $zip = new ZipArchive;
    expect($zip->open($archives[0]->getPathname()))->toBeTrue()
        ->and($zip->locateName('attachments/'.$capturePath))->not->toBeFalse();

    // The archived database must be a readable SQLite file holding the data
    // that was committed at the time of the backup.
    $snapshotPath = $this->backupDirectory.'/extracted.sqlite';
    File::put($snapshotPath, $zip->getFromName('database.sqlite'));
    $zip->close();

    $snapshot = new SQLite3($snapshotPath);
    expect($snapshot->querySingle('select count(*) from customers'))->toBe(1);
    $snapshot->close();
});

it('rotates out the oldest archives beyond the kept count', function () {
    File::ensureDirectoryExists($this->backupDirectory);

    foreach (['2026-01-01-023000', '2026-01-02-023000', '2026-01-03-023000'] as $timestamp) {
        File::put($this->backupDirectory."/shoprelle-{$timestamp}.zip", 'ancienne archive');
    }

    $this->artisan('shoprelle:backup', ['--keep' => 2])->assertSuccessful();

    $remaining = collect(File::files($this->backupDirectory))->map->getFilename();

    // The fresh archive counts in the two kept: only the newest of the old
    // ones survives beside it.
    expect($remaining)->toHaveCount(2)
        ->and($remaining)->toContain('shoprelle-2026-01-03-023000.zip')
        ->and($remaining)->not->toContain('shoprelle-2026-01-02-023000.zip')
        ->and($remaining)->not->toContain('shoprelle-2026-01-01-023000.zip');
});

it('never deletes anything that is not a backup archive', function () {
    File::ensureDirectoryExists($this->backupDirectory);
    File::put($this->backupDirectory.'/notes.txt', 'à garder');

    $this->artisan('shoprelle:backup', ['--keep' => 1])->assertSuccessful();

    expect(File::exists($this->backupDirectory.'/notes.txt'))->toBeTrue();
});
