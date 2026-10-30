<?php

use App\Domain\Manual\Enums\ManualRole;
use App\Domain\Manual\Support\ManualLibrary;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/manuals-'.uniqid();
    File::ensureDirectoryExists("{$this->root}/en/shared");
    File::put("{$this->root}/en/worker.md", <<<'MD'
        # Worker manual

        Welcome <b>here</b>.

        ## Getting started

        1. Open the app.
        2. Tap **Sign in**.

        @include shared/help

        ## Getting started

        Again.
        MD);
    File::put("{$this->root}/en/shared/help.md", "## Need help?\n\nAsk us.\n");
});

afterEach(fn () => File::deleteDirectory($this->root));

it('splits a manual into sections with unique anchors and pulls in shared files', function () {
    $manual = (new ManualLibrary($this->root))->load(ManualRole::Worker, 'en');

    expect($manual->title)->toBe('Worker manual')
        ->and(array_map(fn ($section) => $section->title, $manual->sections))->toBe(['Getting started', 'Need help?', 'Getting started'])
        ->and(array_map(fn ($section) => $section->id, $manual->sections))->toBe(['getting-started', 'need-help', 'getting-started-2'])
        ->and($manual->sections[0]->html)->toContain('<ol>')->toContain('<strong>Sign in</strong>');
});

it('strips raw HTML from the Markdown', function () {
    $manual = (new ManualLibrary($this->root))->load(ManualRole::Worker, 'en');

    expect($manual->introHtml)->toContain('Welcome')->not->toContain('<b>');
});

it('falls back to English when a language has no manual', function () {
    $manual = (new ManualLibrary($this->root))->load(ManualRole::Worker, 'bn');

    expect($manual->translationPending)->toBeTrue()
        ->and($manual->title)->toBe('Worker manual');
});

it('refuses includes nested too deeply', function () {
    File::put("{$this->root}/en/shared/help.md", "@include shared/help\n");

    (new ManualLibrary($this->root))->load(ManualRole::Worker, 'en');
})->throws(RuntimeException::class);
