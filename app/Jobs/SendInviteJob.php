<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\InviteMail;
use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

final class SendInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Invite $invite) {}

    public function handle(): void
    {
        Mail::to($this->invite->email)->send(new InviteMail($this->invite));
    }
}
