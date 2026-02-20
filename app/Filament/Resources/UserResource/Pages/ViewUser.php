<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return trans('filament-users::user.resource.label');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('signatureCollection.short_name')
                            ->label(__('signature_collection.name')),
                        TextEntry::make('roles.name')->badge(),
                    ]),
                Section::make('Login Code')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'super_admin']))
                    ->description('Click the code below to copy it, then open the login URL')
                    ->schema([
                        TextEntry::make('login_code')
                            ->label('Code (click to copy)')
                            ->getStateUsing(function ($record) {
                                if ($record->hasAnyRole(['admin', 'super_admin'])) {
                                    return 'N/A';
                                }
                                $ip = request()->ip();
                                $ipv4 = null;
                                if (is_string($ip) && preg_match('/([0-9]{1,3}(?:\.[0-9]{1,3}){3})/', $ip, $m)) {
                                    $ipv4 = $m[1];
                                }
                                return $record->generateLoginCodeForAdminIP($ipv4 ?? $ip);
                            })
                            ->copyable()
                            ->copyMessage('Code copied!')
                            ->copyMessageDuration(2000)
                            ->extraAttributes(['class' => 'font-mono text-2xl font-bold'])
                            ->color('success'),
                        TextEntry::make('login_url')
                            ->label('Login URL')
                            ->getStateUsing(fn () => route('code-login'))
                            ->url(fn () => route('code-login'), true)
                            ->color('primary'),
                        TextEntry::make('login_code_expiration')
                            ->label('Valid until'),
                        TextEntry::make('login_code_valid_ip')
                            ->label('Valid from IP'),
                    ]),
            ]);
    }
}
