<?php

use App\Models\User;
use App\Services\ErrorLogService;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    $this->logPath = sys_get_temp_dir().'/shoprelle-journal-test-'.uniqid().'.log';
    config()->set('logging.channels.single.path', $this->logPath);
});

afterEach(function () {
    @unlink($this->logPath);
});

it('keeps guests and non-administrators out of the journal', function () {
    $this->get(route('admin.logs.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.logs.index'))
        ->assertForbidden();
});

it('renders empty when no log file exists yet', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/logs/index')
            ->has('entries', 0)
        );
});

it('shows the latest entries, most recent first, with their stack trace as detail', function () {
    file_put_contents($this->logPath, implode("\n", [
        '[2026-08-05 10:00:00] production.ERROR: Premier plantage {"exception":"[object] (RuntimeException(code: 0))"}',
        '#0 /var/www/app/Services/Foo.php(12): boom()',
        '#1 {main}',
        '[2026-08-05 11:30:00] production.WARNING: Espace disque bas',
    ]));

    $this->actingAs($this->admin)
        ->get(route('admin.logs.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/logs/index')
            ->has('entries', 2)
            ->where('entries.0.level', 'WARNING')
            ->where('entries.0.message', 'Espace disque bas')
            ->where('entries.0.detail', null)
            ->where('entries.1.level', 'ERROR')
            ->where('entries.1.timestamp', '2026-08-05 10:00:00')
        );

    $entries = app(ErrorLogService::class)->latest();

    expect($entries[1]['message'])->toContain('Premier plantage')
        ->and($entries[1]['detail'])->toContain('#0 /var/www/app/Services/Foo.php(12)');
});

it('never returns more than the asked number of entries', function () {
    $lines = [];

    foreach (range(1, 60) as $number) {
        $lines[] = sprintf('[2026-08-05 09:%02d:00] production.ERROR: Erreur numéro %d', $number % 60, $number);
    }

    file_put_contents($this->logPath, implode("\n", $lines));

    $entries = app(ErrorLogService::class)->latest(50);

    expect($entries)->toHaveCount(50)
        // Les plus récentes d'abord : la dernière écrite arrive en tête.
        ->and($entries[0]['message'])->toBe('Erreur numéro 60');
});
