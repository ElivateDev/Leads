<?php

namespace App\Filament\Client\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Session;

class ImpersonationBanner extends Widget
{
    protected static string $view = 'filament.client.widgets.impersonation-banner';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function isVisible(): bool
    {
        return Session::has('is_impersonating');
    }
}
