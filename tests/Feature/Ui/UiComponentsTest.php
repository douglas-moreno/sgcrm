<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the primary button with default classes', function (): void {
    $html = Blade::render('<x-ui.button>Save</x-ui.button>');

    expect($html)
        ->toContain('ui-button')
        ->toContain('bg-primary-500')
        ->toContain('Save')
        ->toContain('data-variant="primary"')
        ->toContain('data-size="md"')
        ->toContain('type="button"');
});

it('disables the button when loading', function (): void {
    $html = Blade::render('<x-ui.button loading>Saving</x-ui.button>');

    expect($html)
        ->toContain('disabled')
        ->toContain('animate-spin');
});

it('renders danger variant for the button', function (): void {
    $html = Blade::render('<x-ui.button variant="danger">Delete</x-ui.button>');

    expect($html)
        ->toContain('bg-danger-500')
        ->toContain('data-variant="danger"');
});

it('renders the input with label, hint and error states', function (): void {
    $base = Blade::render('<x-ui.input name="email" label="Email" hint="We never share it." />');
    $errored = Blade::render('<x-ui.input name="email" label="Email" error="Already taken." />');

    expect($base)
        ->toContain('ui-input')
        ->toContain('Email')
        ->toContain('We never share it.')
        ->toContain('name="email"')
        ->toContain('id="email"');

    expect($errored)
        ->toContain('Already taken.')
        ->toContain('border-danger-500')
        ->toContain('data-state="error"');
});

it('renders the textarea with rows and label', function (): void {
    $html = Blade::render('<x-ui.textarea name="notes" label="Notes" rows="6" />');

    expect($html)
        ->toContain('ui-textarea')
        ->toContain('rows="6"')
        ->toContain('name="notes"')
        ->toContain('Notes');
});

it('renders the select with options and selected value', function (): void {
    $html = Blade::render(
        '<x-ui.select name="stage" label="Stage" placeholder="Pick one" :options="$opts" value="b" />',
        ['opts' => ['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']],
    );

    expect($html)
        ->toContain('ui-select')
        ->toContain('Pick one')
        ->toContain('Alpha')
        ->toContain('Beta')
        ->toContain('Gamma')
        ->toMatch('/<option value="b"[^>]*selected[^>]*>\s*Beta/');
});

it('renders the checkbox with checked + error states', function (): void {
    $checked = Blade::render('<x-ui.checkbox name="terms" label="Agree" :checked="true" />');
    $errored = Blade::render('<x-ui.checkbox name="terms" label="Agree" state="error" />');

    expect($checked)
        ->toContain('ui-checkbox')
        ->toContain('checked')
        ->toContain('Agree');

    expect($errored)
        ->toContain('border-danger-500');
});

it('renders the radio with selected + label', function (): void {
    $html = Blade::render('<x-ui.radio name="role" value="owner" label="Business Owner" :checked="true" />');

    expect($html)
        ->toContain('ui-radio')
        ->toContain('Business Owner')
        ->toContain('value="owner"')
        ->toContain('checked');
});

it('renders the modal hidden by default and shown when show=true', function (): void {
    $hidden = Blade::render('<x-ui.modal title="Hi">Body</x-ui.modal>');
    $visible = Blade::render('<x-ui.modal title="Hi" :show="true">Body</x-ui.modal>');

    expect($hidden)
        ->toContain('ui-modal')
        ->toContain('hidden');

    expect($visible)
        ->toContain('flex')
        ->toContain('Hi')
        ->toContain('Body');
});

it('renders toast variants with title and slot', function (): void {
    $html = Blade::render('<x-ui.toast variant="success" title="Saved">All good</x-ui.toast>');

    expect($html)
        ->toContain('ui-toast')
        ->toContain('data-variant="success"')
        ->toContain('Saved')
        ->toContain('All good');
});

it('renders the card with title, subtitle and footer', function (): void {
    $html = Blade::render(<<<'HTML'
        <x-ui.card title="Acme" subtitle="acme@example.com">
            Body
            <x-slot:footer>FooterContent</x-slot:footer>
        </x-ui.card>
    HTML);

    expect($html)
        ->toContain('ui-card')
        ->toContain('Acme')
        ->toContain('acme@example.com')
        ->toContain('Body')
        ->toContain('FooterContent');
});

it('renders badge variants', function (): void {
    $html = Blade::render('<x-ui.badge variant="success">Won</x-ui.badge>');

    expect($html)
        ->toContain('ui-badge')
        ->toContain('Won')
        ->toContain('data-variant="success"')
        ->toContain('bg-success-100');
});

it('renders avatar with initials when no image is provided', function (): void {
    $html = Blade::render('<x-ui.avatar name="Carla Souza" />');

    expect($html)
        ->toContain('ui-avatar')
        ->toContain('CS');
});

it('renders avatar with image src when provided', function (): void {
    $html = Blade::render('<x-ui.avatar name="Ana" src="/avatars/ana.jpg" />');

    expect($html)
        ->toContain('<img')
        ->toContain('/avatars/ana.jpg')
        ->toContain('alt="Ana"');
});
