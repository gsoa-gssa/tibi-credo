<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\BlogPostsOverview;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;


class AppPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([10, 25, 50]);
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): string => Blade::render('<div class="mt-4 text-center"><x-filament::link :href="route(\'code-login\')" size="sm">{{ __(\'code_login.use_code\') }}</x-filament::link></div>')
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->brandName(function () {
                $user = auth()->user();
                if ($user && $user->signatureCollection && $user->signatureCollection->short_name) {
                    return 'Certimi - ' . $user->signatureCollection->short_name;
                }
                return 'Certimi';
            })
            ->id('app')
            ->path('')
            ->login()
            // ->colors([
            //     'primary' => "#b40e44",
            //     'gray' => Color::Slate,
            // ])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Resources\CountingResource\Widgets\SignatureCountStats::class,
                \App\Filament\Resources\MaeppliResource\Widgets\SignatureCountStats::class,
                \App\Filament\Resources\BoxResource\Widgets\BoxStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->passwordReset()
            ->plugins([
                SpotlightPlugin::make(),
                \TomatoPHP\FilamentUsers\FilamentUsersPlugin::make(),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Rupadana\ApiService\ApiServicePlugin::make(),
                ActivitylogPlugin::make()
                    ->navigationItem(false)
                    ->navigationGroup(__('navigation.group.control')),
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.workflows')),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.projectDataManagement'))
                    ->collapsed(true),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.geoData'))
                    ->collapsed(true),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.control'))
                    ->collapsed(true),
                \Filament\Navigation\NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.systemSettings'))
                    ->collapsed(true),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('System Settings'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render("@livewire('signature-collection-selector')")
            );
    }
}
