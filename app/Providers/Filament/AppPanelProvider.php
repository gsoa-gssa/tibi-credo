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
use App\Filament\Resources\SignatureCollectionResource\Widgets\CountingChart;
use App\Filament\Resources\SignatureCollectionResource\Widgets\ValidityChart;


class AppPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([10, 25, 50])
                ->emptyStateHeading(function () use ($table) {
                    try {
                        $model = $table->getModel();
                        if ($model) {
                            $resourceClass = \Filament\Facades\Filament::getModelResource($model);
                            if ($resourceClass && method_exists($resourceClass, 'getPluralModelLabel')) {
                                return __('general.empty_table', ['model_name' => $resourceClass::getPluralModelLabel()]);
                            }
                        }
                    } catch (\Exception $e) {
                        // Silently ignore if model can't be determined
                    }
                    return null;
                });
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): string => Blade::render('<div class="mt-4 text-center"><x-filament::link :href="route(\'code-login\')" size="sm">{{ __(\'code_login.use_code\') }}</x-filament::link></div>')
        );
    }

    public function panel(Panel $panel): Panel
    {
        $isDebug = config('app.debug');
        $panel = $panel
            ->default()
            ->colors(function () use ($isDebug) {
                if ($isDebug) {
                    // Use a distinct color palette for debug mode
                    return [
                        'primary' => Color::Pink,
                    ];
                } else {
                    return [];
                }
            })
            ->brandName(function () use ($isDebug) {
                if ($isDebug) {
                    $base_name = 'DEBUG';
                } else {
                    $base_name = 'Certimi';
                }
                $user = auth()->user();
                if ($user && $user->signatureCollection && $user->signatureCollection->short_name) {
                    return $base_name . ' - ' . $user->signatureCollection->short_name;
                }
                return $base_name;
            })
            ->id('app')
            ->path('')
            ->login()
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
                \Filament\View\PanelsRenderHook::BODY_END,
                function (): string {
                    $user = auth()->user();
                    \Log::debug('Rendering viewport border for user', ['user_id' => $user ? $user->id : null]);
                    $color = null;
                    if ($user && $user->signatureCollection && $user->signatureCollection->color) {
                        \Log::debug('User has signature collection with color', ['color' => $user->signatureCollection->color]);
                        $color = $user->signatureCollection->color;
                    }
                    $style = config('app.debug') ? 'dashed' : 'solid';
                    return \Illuminate\Support\Facades\Blade::render('<x-viewport-border style="'.$style.'" color="'.$color.'" />');
                }
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render("@livewire('admin-warning')@livewire('signature-collection-selector')")
            );

        return $panel;
    }
}
