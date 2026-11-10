<?php

use Symfony\Component\Finder\Finder;

/*
 * Required and optional fields (D-118): required fields show an asterisk from the shared Label,
 * optional fields show nothing. These guards stop "(optional)" wording from coming back.
 */

it('has no "optional" wording in any interface string', function () {
    foreach (glob(lang_path('*.json')) as $file) {
        $strings = json_decode((string) file_get_contents($file), true);

        $flagged = array_keys(array_filter(
            $strings,
            fn (string $text) => preg_match('/\((optional|ঐচ্ছিক)\)|^(Optional\.|ঐচ্ছিক।)/iu', $text) === 1,
        ));

        expect($flagged)->toBe([], basename($file).' still marks fields as optional')
            ->and($strings)->not->toHaveKey('common.optional');
    }
});

it('marks no field as optional in the pages and components', function () {
    $files = Finder::create()->files()->in(resource_path('js'))->name('*.tsx')->notPath('components/ui');

    $flagged = collect($files)
        ->filter(fn (SplFileInfo $file) => preg_match('/common\\.optional|\\(optional\\)/i', (string) file_get_contents($file->getPathname())) === 1)
        ->map(fn (SplFileInfo $file) => str($file->getPathname())->after(resource_path('js').'/')->toString())
        ->values()
        ->all();

    expect($flagged)->toBe([]);
});

it('draws the required asterisk in the shared Label, hidden from assistive tech', function () {
    $label = (string) file_get_contents(resource_path('js/components/ui/label.tsx'));

    expect($label)->toContain('required?: boolean')
        ->and($label)->toContain('aria-hidden="true"')
        ->and($label)->toContain('data-slot="required-marker"');
});
