<?php

declare(strict_types=1);

arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring([
        'App\Models\Concerns',
        'App\Models\Scopes',
    ]);

arch('user is an Authenticatable')
    ->expect('App\Models\User')
    ->toExtend('Illuminate\Foundation\Auth\User');

arch('livewire components extend Livewire Component')
    ->expect('App\Livewire')
    ->classes()
    ->toExtend('Livewire\Component');

arch('policies live in App\Policies')
    ->expect('App\Policies')
    ->classes()
    ->toBeFinal();

arch('no debug helpers in app/')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('app code uses strict types')
    ->expect('App')
    ->toUseStrictTypes();
