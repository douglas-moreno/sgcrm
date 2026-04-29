<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

it('exposes the Faker helper for test data', function (): void {
    expect(fake())->toBeInstanceOf(Faker\Generator::class);
    expect(fake()->word())->toBeString();
});

it('wires RefreshDatabase to Feature tests via Pest configuration', function (): void {
    $contents = file_get_contents(__DIR__.'/../Pest.php');

    expect($contents)
        ->toContain(RefreshDatabase::class)
        ->toContain("->in('Feature')");
});
