<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class SettingsPage extends Component
{
    public function render(): View
    {
        return view('livewire.settings.settings-page');
    }
}
