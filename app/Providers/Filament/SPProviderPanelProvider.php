<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Hasnayeen\Themes\ThemesPlugin;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SPProviderPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sPProvider')
            ->favicon(asset('assets/brand_images/favicon.png'))
            ->path('sPProvider')
            ->login()
            ->authGuard('spprovider') // 👈 important
            ->brandName('SP Provider Panel')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/SPProvider/Resources'), for: 'App\\Filament\\SPProvider\\Resources')
            ->discoverPages(in: app_path('Filament/SPProvider/Pages'), for: 'App\\Filament\\SPProvider\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/SPProvider/Widgets'), for: 'App\\Filament\\SPProvider\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                SetTheme::class,
            ])
            ->plugins(
                $this->getPlugins()
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function auth()
    {
        // Return a callback-based auth provider
        return new class
        {
            public function loginUrl(): string
            {
                return '/sPProvider/login';
            }

            // public function access($user): bool
            // {
            //     // Here you check if the user is a ClientUser and has role 'Client'
            //     return $user instanceof User && $user->hasRole('Candidate');
            // }
        };
    }

    private function getPlugins(): array
    {
        $plugins = [
            ThemesPlugin::make(),
            // FilamentShieldPlugin::make(), // already removed earlier
            // ApiServicePlugin::make(),    // 👈 remove for customers
            BreezyCore::make()
                ->myProfile()

        ];

        return $plugins;
    }
}
