<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\EvolutionWebhookController;
use App\Livewire\Deals\Show as DealShow;
use App\Livewire\Invites\AcceptInvite;
use App\Livewire\Kanban\Board;
use App\Livewire\Leads\EditLead;
use App\Livewire\Leads\LeadList;
use App\Livewire\Reports\ActivityVolume;
use App\Livewire\Reports\LossReasons;
use App\Livewire\Reports\PipelineOverview;
use App\Livewire\Reports\SalespersonPerformance;
use App\Livewire\Settings\SettingsPage;
use App\Livewire\Team\InviteList;
use App\Livewire\Team\UserList;
use App\Livewire\Whatsapp\Conversation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('kanban') : redirect()->route('login');
});

Route::get('/dev/ui', function () {
    abort_if(App::environment('production'), 404);

    return view('dev.ui-preview');
})->name('dev.ui');

Route::get('/invites/{token}', AcceptInvite::class)->name('invites.accept');

Route::post('/webhooks/evolution/{user}', EvolutionWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.evolution');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/kanban', Board::class)->name('kanban');
    Route::get('/leads', LeadList::class)->name('leads.index');
    Route::get('/leads/{lead}', EditLead::class)->name('leads.edit');
    Route::get('/deals/{deal}', DealShow::class)->name('deals.show');
    Route::middleware('role:business_owner')->prefix('reports')->name('reports.')->group(function (): void {
        Route::redirect('/', '/reports/pipeline')->name('index');
        Route::get('/pipeline', PipelineOverview::class)->name('pipeline');
        Route::get('/salesperson', SalespersonPerformance::class)->name('salesperson');
        Route::get('/loss-reasons', LossReasons::class)->name('loss-reasons');
        Route::get('/activity', ActivityVolume::class)->name('activity');
    });
    Route::get('/team', UserList::class)->name('team.index');
    Route::get('/team/invites', InviteList::class)->name('team.invites');
    Route::get('/settings', SettingsPage::class)->name('settings.index');
    Route::get('/whatsapp/leads/{lead}', Conversation::class)->name('whatsapp.conversation');
});
