<?php

namespace App\Livewire\Auth;

use Filament\Pages\SimplePage;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;

class CodeLogin extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'livewire.auth.code-login';

    public ?array $data = [];

    public function mount(): void
    {
        if (Auth::check()) {
            redirect()->intended(filament()->getUrl());
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label(__('code_login.code'))
                    ->placeholder('ABC123')
                    ->required()
                    ->maxLength(6)
                    ->minLength(6)
                    ->autocomplete('off')
                    ->autofocus()
                    ->extraInputAttributes([
                        'class' => '!text-4xl font-bold tracking-widest text-center',
                        'style' => 'text-transform: uppercase; font-size: 2.25rem !important;'
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            $this->rateLimit(10);
        } catch (\Exception $exception) {
            Notification::make()
                ->danger()
                ->title(__('code_login.too_many_attempts'))
                ->send();
            return;
        }

        $data = $this->form->getState();
        $ip = request()->ip();
        $code = strtoupper($data['code']);

        $user = User::where('login_code', $code)
            ->whereNotNull('login_code_expiration')
            ->first();

        if (!$user) {
            Notification::make()
                ->danger()
                ->title(__('code_login.code_invalid'))
                ->send();
            $this->form->fill(['code' => '']);
            return;
        }

        if (now()->isAfter($user->login_code_expiration)) {
            Notification::make()
                ->danger()
                ->title(__('code_login.code_expired'))
                ->send();
            $this->form->fill(['code' => '']);
            return;
        }

        if ($user->login_code_valid_ip !== $ip) {
            Notification::make()
                ->danger()
                ->title(__('code_login.code_invalid_ip'))
                ->send();
            $this->form->fill(['code' => '']);
            return;
        }

        // Prevent logging in admins via code
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            Notification::make()
                ->danger()
                ->title(__('code_login.invalid_user'))
                ->send();
            $this->form->fill(['code' => '']);
            return;
        }

        // Clear code after use (single-use semantics)
        $user->login_code = null;
        $user->login_code_expiration = null;
        $user->login_code_valid_ip = null;
        $user->save();

        Auth::login($user);

        redirect()->intended(filament()->getUrl());
    }
}
