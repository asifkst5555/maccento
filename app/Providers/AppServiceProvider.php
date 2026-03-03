<?php

namespace App\Providers;

use App\Models\PanelNotification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.panel', function ($view): void {
            if (!auth()->check()) {
                return;
            }

            $userId = (int) auth()->id();
            $unreadCount = PanelNotification::query()
                ->where('user_id', $userId)
                ->whereNull('read_at')
                ->count();

            $items = PanelNotification::query()
                ->where('user_id', $userId)
                ->latest('id')
                ->limit(12)
                ->get();

            $view->with('panelUnreadNotifications', $unreadCount);
            $view->with('panelNotifications', $items);
        });
    }
}
