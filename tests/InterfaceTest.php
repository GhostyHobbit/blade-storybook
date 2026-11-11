<?php

declare(strict_types=1);

use ItsRD\BladeStorybook\Tests\Fixtures\Components\FixtureButton;
use ItsRD\BladeStorybook\Tests\Fixtures\Components\FixtureCard;

it('shows the first component when none is selected', function (): void {
    $this->get(route('blade-storybook.index'))
        ->assertOk()
        ->assertSee('Fixture Card')
        ->assertSee('&lt;x-fixture-card&gt;', false);
});

it('names the constructor parameters that are missing the prop attribute', function (): void {
    $this->get(route('blade-storybook.index', [
        'component' => str_replace('\\', '.', FixtureButton::class),
    ]))
        ->assertOk()
        ->assertSee('meta, internal');
});

it('renders the selected story in the preview', function (): void {
    $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureButton::class),
        'story' => 'secondary-small',
    ]))
        ->assertOk()
        ->assertSee('class="secondary size-2"', false)
        ->assertSee('data-meta="cancel"', false)
        ->assertSee('data-internal="none"', false)
        ->assertSee('Annuleren');
});

it('lets controls override story values', function (): void {
    $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureButton::class),
        'story' => 'primary',
        'props' => ['variant' => 'secondary', 'disabled' => '1'],
        'slots' => ['default' => 'Verzenden'],
    ]))
        ->assertOk()
        ->assertSee('class="secondary size-1" data-href="none" data-meta="none" data-internal="none" disabled', false)
        ->assertSee('Verzenden');
});

it('explains why a component cannot be previewed instead of crashing', function (): void {
    $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureCard::class),
    ]))
        ->assertOk()
        ->assertSee('publishedAt');
});

it('adds raw head markup to the preview', function (): void {
    config()->set('blade-storybook.preview.head', '<link rel="stylesheet" href="https://fonts.example/nunito.css">');

    $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureButton::class),
    ]))
        ->assertOk()
        ->assertSee('<link rel="stylesheet" href="https://fonts.example/nunito.css">', false);
});

it('includes a head view in the preview', function (): void {
    view()->addNamespace('storybook-test', __DIR__.'/Fixtures/views');
    config()->set('blade-storybook.preview.head', 'storybook-test::head');

    $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureButton::class),
    ]))
        ->assertOk()
        ->assertSee('<meta name="storybook-head-fixture" content="1">', false);
});

it('adds raw head and scripts markup to the preview', function (): void {
    config()->set('blade-storybook.preview.head', '<link rel="stylesheet" href="https://fonts.example/nunito.css">');
    config()->set('blade-storybook.preview.scripts', '<script src="https://cdn.example/alpine.js" defer></script>');

    $response = $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureButton::class),
    ]))->assertOk();

    $content = (string) $response->getContent();

    expect(strpos($content, 'fonts.example'))->toBeLessThan(strpos($content, '</head>'))
        ->and(strpos($content, 'cdn.example'))->toBeGreaterThan(strpos($content, '<body'));
});

it('renders head and scripts views in the preview', function (): void {
    view()->addNamespace('storybook-test', __DIR__.'/Fixtures/views');
    config()->set('blade-storybook.preview.head', 'storybook-test::head');
    config()->set('blade-storybook.preview.scripts', 'storybook-test::scripts');

    $this->get(route('blade-storybook.preview', [
        'component' => str_replace('\\', '.', FixtureButton::class),
    ]))
        ->assertOk()
        ->assertSee('<meta name="storybook-head-fixture" content="1">', false)
        ->assertSee('<script data-storybook-scripts-fixture></script>', false);
});

it('returns 404 for an unknown component', function (): void {
    $this->get(route('blade-storybook.preview', ['component' => 'Nope']))->assertNotFound();
});

it('serves the interface assets', function (): void {
    $this->get(route('blade-storybook.asset', 'storybook.css'))
        ->assertOk()
        ->assertHeader('content-type', 'text/css; charset=UTF-8');
});
