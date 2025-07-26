<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('client')
            ->favicon(asset('images/favicon.png'))
            ->login()
            ->authGuard('web')
            ->brandLogoHeight('2rem')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->pages([
                \App\Filament\Client\Pages\Dashboard::class,
                \App\Filament\Client\Pages\LeadBoard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                \App\Filament\Client\Widgets\ImpersonationBanner::class,
                \App\Filament\Client\Widgets\LeadStatsWidget::class,
            ])
            ->profile()
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn(): string => Auth::user()?->name ?? 'Guest')
                    ->icon('heroicon-m-user-circle')
                    ->url(fn(): string => filament()->getProfileUrl()),
                'settings' => MenuItem::make()
                    ->label('Settings')
                    ->url(fn(): string => \App\Filament\Client\Resources\ClientResource::getUrl('index'))
                    ->icon('heroicon-o-cog-6-tooth'),
                'change_password' => MenuItem::make()
                    ->label('Change Password')
                    ->url(fn(): string => filament()->getProfileUrl())
                    ->icon('heroicon-m-key'),
                'logout' => MenuItem::make()
                    ->label('Sign out')
                    ->url(fn(): string => filament()->getLogoutUrl())
                    ->icon('heroicon-m-arrow-left-on-rectangle'),
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
                \App\Http\Middleware\ImpersonationMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
