<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UI Preview · sgCrm</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt min-h-screen p-8" data-testid="ui-preview">
    <div class="max-w-5xl mx-auto space-y-10">
        <header>
            <h1 class="text-2xl font-semibold text-ink">sgCrm — UI Component Preview</h1>
            <p class="text-sm text-ink-muted">Reference assets: <code>docs/design/</code>. Visible only in non-production environments.</p>
        </header>

        <section data-testid="section-buttons">
            <h2 class="text-lg font-semibold text-ink mb-3">Buttons</h2>
            <div class="flex flex-wrap gap-3">
                <x-ui.button>Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="outline">Outline</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="danger">Danger</x-ui.button>
                <x-ui.button variant="success">Success</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
                <x-ui.button loading>Loading</x-ui.button>
                <x-ui.button size="sm">Small</x-ui.button>
                <x-ui.button size="lg">Large</x-ui.button>
            </div>
        </section>

        <section data-testid="section-inputs">
            <h2 class="text-lg font-semibold text-ink mb-3">Inputs</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.input name="email" label="Email" placeholder="you@example.com" />
                <x-ui.input name="phone" label="Phone" hint="Use international format." />
                <x-ui.input name="error_demo" label="With error" error="Email already in use." value="bad" />
                <x-ui.textarea name="notes" label="Notes" rows="3" placeholder="Internal context…" />
            </div>
        </section>

        <section data-testid="section-selects">
            <h2 class="text-lg font-semibold text-ink mb-3">Select</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.select name="stage" label="Pipeline stage" placeholder="Choose a stage" :options="[
                    'new_lead' => 'New Lead',
                    'contacted' => 'Contacted',
                    'proposal_sent' => 'Proposal Sent',
                    'negotiation' => 'Negotiation',
                    'won' => 'Won',
                    'lost' => 'Lost',
                ]" />
                <x-ui.select name="owner" label="Owner" :options="['1' => 'Ana', '2' => 'Bruno', '3' => 'Carla']" value="2" />
            </div>
        </section>

        <section data-testid="section-checks">
            <h2 class="text-lg font-semibold text-ink mb-3">Checkbox &amp; Radio</h2>
            <div class="flex flex-wrap gap-6">
                <x-ui.checkbox name="terms" label="Accept terms" />
                <x-ui.checkbox name="terms2" label="Pre-checked" :checked="true" />
                <x-ui.checkbox name="terms3" label="Disabled" disabled />
                <x-ui.checkbox name="terms4" label="Error state" state="error" />
            </div>
            <div class="flex flex-wrap gap-6 mt-3">
                <x-ui.radio name="role" value="owner" label="Business Owner" :checked="true" />
                <x-ui.radio name="role" value="sales" label="Salesperson" />
                <x-ui.radio name="role2" value="x" label="Disabled" disabled />
            </div>
        </section>

        <section data-testid="section-feedback">
            <h2 class="text-lg font-semibold text-ink mb-3">Toasts &amp; Badges</h2>
            <div class="space-y-2">
                <x-ui.toast variant="info" title="Heads up">Your WhatsApp instance is reconnecting.</x-ui.toast>
                <x-ui.toast variant="success" title="Saved">Deal moved to Negotiation.</x-ui.toast>
                <x-ui.toast variant="warning" title="Reminder">Trial ends in 2 days.</x-ui.toast>
                <x-ui.toast variant="danger" title="Failure">Message could not be delivered.</x-ui.toast>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <x-ui.badge>Neutral</x-ui.badge>
                <x-ui.badge variant="primary">Primary</x-ui.badge>
                <x-ui.badge variant="success">Won</x-ui.badge>
                <x-ui.badge variant="warning">Negotiation</x-ui.badge>
                <x-ui.badge variant="danger">Lost</x-ui.badge>
                <x-ui.badge variant="accent">Custom</x-ui.badge>
            </div>
        </section>

        <section data-testid="section-card">
            <h2 class="text-lg font-semibold text-ink mb-3">Cards</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.card title="Acme Corp" subtitle="ana@acme.com">
                    <p class="text-sm text-ink">Deal · R$ 12.500,00 · Negotiation</p>
                    <x-slot:footer>
                        <x-ui.button size="sm" variant="secondary">Open</x-ui.button>
                    </x-slot:footer>
                </x-ui.card>
                <x-ui.card>
                    <div class="flex items-center gap-3">
                        <x-ui.avatar name="Carla Souza" />
                        <div>
                            <p class="text-sm font-semibold text-ink">Carla Souza</p>
                            <p class="text-xs text-ink-muted">Salesperson</p>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </section>

        <section data-testid="section-modal">
            <h2 class="text-lg font-semibold text-ink mb-3">Modal (always visible for preview)</h2>
            <x-ui.modal name="demo" title="Confirm action" :show="true">
                Are you sure you want to mark this deal as Lost?
                <x-slot:footer>
                    <x-ui.button variant="ghost">Cancel</x-ui.button>
                    <x-ui.button variant="danger">Confirm</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </section>
    </div>
</body>
</html>
